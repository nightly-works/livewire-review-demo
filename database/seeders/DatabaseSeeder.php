<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $team = Team::factory()->create(['name' => 'Sales']);
        $manager = User::factory()->create(['name' => 'Maya Manager', 'email' => 'maya@example.test', 'team_id' => $team->id]);
        $manager->forceFill(['is_manager' => true])->save();
        $employee = User::factory()->create(['name' => 'Eli Employee', 'email' => 'eli@example.test', 'team_id' => $team->id]);
        Expense::factory()->for($employee)->create();
    }
}
