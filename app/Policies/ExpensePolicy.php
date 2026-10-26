<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /** The employee edits their own expense while it waits for approval. */
    public function update(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id && $expense->status === 'pending';
    }

    /** A manager decides on the pending expenses of their own team, never on their own. */
    public function approve(User $user, Expense $expense): bool
    {
        return $user->is_manager
            && $expense->status === 'pending'
            && $expense->user_id !== $user->id
            && $expense->user->team_id === $user->team_id;
    }
}
