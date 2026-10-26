<?php

namespace Tests\Feature;

use App\Livewire\EditExpense;
use App\Livewire\ExpenseList;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The four findings of the security review, each written as the request a user could send from the
 * browser: Livewire::test() sends property updates and method calls the way the browser does, whatever
 * the page shows. On the app as delivered (tag as-delivered) every numbered test fails; after the fixes
 * they pass. The other tests pin normal use.
 */
class LivewireSecurityReviewTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $eli;

    private User $maya;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
        $this->eli = User::factory()->create(['team_id' => $this->team->id]);
        $this->maya = User::factory()->create(['team_id' => $this->team->id]);
        $this->maya->forceFill(['is_manager' => true])->save();
    }

    /** Sends a property update and ignores Livewire refusing it: what matters is what reaches the database. */
    private function tryToSet($component, string $property, $value): void
    {
        try {
            $component->set($property, $value);
        } catch (\Throwable) {
            // Refused by Livewire: the attack stops here.
        }
    }

    // Finding 1: the form's expense id is a plain public property, so a user can point it at anyone's expense.

    public function test_1_changing_the_expense_id_in_the_browser_does_not_edit_someone_elses_expense(): void
    {
        $mine = Expense::factory()->for($this->eli)->create(['description' => 'Train ticket']);
        $theirs = Expense::factory()->create(['description' => 'Client dinner', 'amount_cents' => 18000]);

        $this->actingAs($this->eli);
        $form = Livewire::test(EditExpense::class, ['expense' => $mine]);
        $this->tryToSet($form, 'expenseId', $theirs->id);
        try {
            $form->set('description', 'Changed')->set('amount', '1.00')->call('save');
        } catch (\Throwable) {
            // Refused: fine, as long as nothing changed below.
        }

        $this->assertSame('Client dinner', $theirs->fresh()->description);
        $this->assertSame(18000, $theirs->fresh()->amount_cents);
    }

    // Finding 2: approve() is public, so any user can call it; only the button is hidden from non-managers.

    public function test_2_an_employee_cannot_approve_an_expense_from_the_browser(): void
    {
        $mine = Expense::factory()->for($this->eli)->create();
        $otherTeam = User::factory()->create(['team_id' => Team::factory()->create()->id]);
        $otherTeam->forceFill(['is_manager' => true])->save();

        $this->actingAs($this->eli);
        Livewire::test(ExpenseList::class)->call('approve', $mine->id)->assertForbidden();
        $this->assertSame('pending', $mine->fresh()->status);

        $this->actingAs($otherTeam);
        Livewire::test(ExpenseList::class)->call('approve', $mine->id)->assertForbidden();
        Livewire::test(ExpenseList::class)->call('reject', $mine->id)->assertForbidden();
        $this->assertSame('pending', $mine->fresh()->status);
    }

    // Finding 3: status is a public property the form never shows, and save() writes it back.

    public function test_3_an_employee_cannot_set_the_status_from_the_browser(): void
    {
        $mine = Expense::factory()->for($this->eli)->create();

        $this->actingAs($this->eli);
        $form = Livewire::test(EditExpense::class, ['expense' => $mine]);
        $this->tryToSet($form, 'status', 'approved');
        $form->set('description', 'Taxi')->call('save');

        $this->assertSame('Taxi', $mine->fresh()->description);
        $this->assertSame('pending', $mine->fresh()->status);
    }

    // Finding 4: any file is accepted as a receipt and stored on the public disk, under the app's domain.

    public function test_4_a_receipt_must_be_a_pdf_or_image_and_is_not_public(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $mine = Expense::factory()->for($this->eli)->create();

        $this->actingAs($this->eli);
        Livewire::test(EditExpense::class, ['expense' => $mine])
            ->set('receipt', UploadedFile::fake()->createWithContent('receipt.html', '<script>fetch("/expenses")</script>'))
            ->call('save')
            ->assertHasErrors('receipt');
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNull($mine->fresh()->receipt_path);

        Livewire::test(EditExpense::class, ['expense' => $mine])
            ->set('receipt', UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNotNull($mine->fresh()->receipt_path);
    }

    // Normal use.

    public function test_employee_edits_their_pending_expense(): void
    {
        $mine = Expense::factory()->for($this->eli)->create();

        $this->actingAs($this->eli)->get(route('expenses.edit', $mine))->assertOk()->assertSee('Edit expense');
        Livewire::test(EditExpense::class, ['expense' => $mine])
            ->set('description', 'Hotel, Lyon')->set('amount', '89.50')->call('save')
            ->assertRedirect(route('expenses'));

        $this->assertSame('Hotel, Lyon', $mine->fresh()->description);
        $this->assertSame(8950, $mine->fresh()->amount_cents);
    }

    public function test_the_edit_page_of_someone_elses_expense_is_forbidden(): void
    {
        $theirs = Expense::factory()->create();

        $this->actingAs($this->eli)->get(route('expenses.edit', $theirs))->assertForbidden();
    }

    public function test_receipt_is_served_to_the_employee_and_their_manager_only(): void
    {
        Storage::fake('local');
        $mine = Expense::factory()->for($this->eli)->create();
        $this->actingAs($this->eli);
        Livewire::test(EditExpense::class, ['expense' => $mine])
            ->set('receipt', UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'))
            ->call('save');
        $stranger = User::factory()->create(['team_id' => Team::factory()->create()->id]);

        $this->actingAs($this->eli)->get(route('expenses.receipt', $mine))->assertOk()->assertDownload();
        $this->actingAs($this->maya)->get(route('expenses.receipt', $mine))->assertOk();
        $this->actingAs($stranger)->get(route('expenses.receipt', $mine))->assertForbidden();
    }

    public function test_manager_approves_and_rejects_their_teams_expenses(): void
    {
        [$a, $b] = Expense::factory()->for($this->eli)->count(2)->create();

        $this->actingAs($this->maya);
        Livewire::test(ExpenseList::class)->assertSee($a->description)->call('approve', $a->id)->call('reject', $b->id);

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('rejected', $b->fresh()->status);
    }
}
