<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Component;

/** The employee's own expenses and, for a manager, the team's expenses waiting for a decision. */
class ExpenseList extends Component
{
    public function approve($expenseId)
    {
        Expense::findOrFail($expenseId)->update(['status' => 'approved']);
    }

    public function reject($expenseId)
    {
        Expense::findOrFail($expenseId)->update(['status' => 'rejected']);
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
