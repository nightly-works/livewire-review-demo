<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Component;

/** The employee's own expenses and, for a manager, the team's expenses waiting for a decision. */
class ExpenseList extends Component
{
    // Any public method can be called from the browser, whether or not the page shows its button:
    // each one checks the policy itself.
    public function approve($expenseId)
    {
        $this->decide($expenseId, 'approved');
    }

    public function reject($expenseId)
    {
        $this->decide($expenseId, 'rejected');
    }

    private function decide($expenseId, string $status): void
    {
        $expense = Expense::findOrFail($expenseId);
        $this->authorize('approve', $expense);
        $expense->forceFill(['status' => $status])->save();
    }

    public function render()
    {
        $user = auth()->user();

        $toApprove = $user->is_manager
            ? Expense::with('user')
                ->where('status', 'pending')
                ->whereRelation('user', 'team_id', $user->team_id)
                ->where('user_id', '!=', $user->id)
                ->latest()
                ->get()
            : collect();

        return view('livewire.expense-list', [
            'mine' => $user->expenses()->latest()->get(),
            'toApprove' => $toApprove,
        ]);
    }
}
