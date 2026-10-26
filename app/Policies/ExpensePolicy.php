<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /** The employee and the managers of their team see the expense and its receipt. */
    public function view(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id
            || ($user->is_manager && $user->team_id !== null && $expense->user->team_id === $user->team_id);
    }

    /** The employee edits their own expense while it waits for approval. */
    public function update(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id && $expense->status === 'pending';
    }

    /** A manager decides on the pending expenses of their own team, never on their own. */
    public function approve(User $user, Expense $expense): bool
    {
        return $user->is_manager
            && $user->team_id !== null
            && $expense->status === 'pending'
            && $expense->user_id !== $user->id
            && $expense->user->team_id === $user->team_id;
    }
}
