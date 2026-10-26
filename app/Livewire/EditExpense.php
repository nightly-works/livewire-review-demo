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

    public $status;

    public $receipt;

    public function mount(Expense $expense)
    {
        $this->authorize('update', $expense);

        $this->expenseId = $expense->id;
        $this->description = $expense->description;
        $this->amount = number_format($expense->amount_cents / 100, 2, '.', '');
        $this->status = $expense->status;
    }

    public function save()
    {
        $this->validate([
            'description' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01|max:5000',
        ]);

        $expense = Expense::findOrFail($this->expenseId);
        $this->authorize('update', $expense);

        $data = [
            'description' => $this->description,
            'amount_cents' => (int) round($this->amount * 100),
            'status' => $this->status,
        ];

        if ($this->receipt) {
            $data['receipt_path'] = $this->receipt->store('receipts', 'public');
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
