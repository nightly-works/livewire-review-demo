<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Expense> */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => 'Train ticket',
            'amount_cents' => 4200,
            'status' => 'pending',
        ];
    }
}
