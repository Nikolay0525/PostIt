<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            UserSeeder::class,
            GroupSeeder::class,
            StaticAccountsSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            // After the comments: its groups and posts are its own, and it needs the accounts.
            SanctionSeeder::class,
            VoteSeeder::class,
            // Last, so these fresh posts have no votes or comments yet: they read as just posted.
            UnreadPostsSeeder::class,
        ]);
    }
}
