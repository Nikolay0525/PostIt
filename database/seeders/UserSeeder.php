<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'user1@example.com')->exists()) {
            return;
        }

        User::factory()->admin()->create(['email' => 'user1@example.com']);

        User::factory()
            ->count(9)
            ->sequence(fn ($sequence) => ['email' => 'user' . ($sequence->index + 2) . '@example.com'])
            ->create();
    }
}
