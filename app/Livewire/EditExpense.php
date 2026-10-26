<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/** The employee's form for one of their pending expenses, with its receipt. */
class EditExpense extends Component
{
    use WithFileUploads;

    // Locked: the browser sends every public property back, and Livewire refuses a changed locked one.
    #[Locked]
    public $expenseId;

    public $description;

    public $amount;

    public $receipt;

    public function mount(Expense $expense)
    {
        $this->authorize('update', $expense);

        $this->expenseId = $expense->id;
        $this->description = $expense->description;
        $this->amount = number_format($expense->amount_cents / 100, 2, '.', '');
    }

    public function save()
    {
        $this->validate([
            'description' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01|max:5000',
            // Livewire's temporary upload accepts any file up to 12 MB: the type is checked here, on content.
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $expense = Expense::findOrFail($this->expenseId);
        $this->authorize('update', $expense);

        $data = [
            'description' => $this->description,
            'amount_cents' => (int) round($this->amount * 100),
        ];

        if ($this->receipt) {
            // Private disk: receipts are served by ReceiptController after a policy check, never by URL.
            $data['receipt_path'] = $this->receipt->store('receipts', 'local');
        }

        $expense->update($data);

        session()->flash('saved', 'Expense saved.');

        return $this->redirect(route('expenses'));
    }

    public function render()
    {
        return view('livewire.edit-expense');
    }
}
