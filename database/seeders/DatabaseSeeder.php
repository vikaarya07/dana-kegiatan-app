<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@vikaarya07.my.id',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Tamu',
            'email' => 'guest@vikaarya07.my.id',
            'email_verified_at' => now(),
        ]);

        Activity::factory()
            ->count(5)
            ->for($user, 'creator')
            ->create();

        Income::factory()
            ->count(10)
            ->for($user, 'creator')
            ->create();

        Expense::factory()
            ->count(15)
            ->for($user, 'creator')
            ->create();
    }
}
