<?php

namespace Database\Seeders;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\SpeakingLanguage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $languageIds = SpeakingLanguage::pluck('id');
        $userIds = User::pluck('id');

        $groups = [
            ['Laravel Developers', 'Tips, questions and showcases for people building with Laravel.', 'Be kind. No spam. Put the Laravel version in questions.', false],
            ['Hiking & Trails', 'Routes, photos and gear advice from people who love the outdoors.', 'Share your route details. Leave no trace.', false],
            ['Home Cooking', 'Recipes and kitchen tricks from everyday cooks.', 'Credit the original author. No ads.', false],
            ['Retro Gaming', 'Old consoles, cartridges and the games we still love.', 'No piracy links.', false],
            ['Private Book Club', 'A small invite-only group for monthly book discussions.', 'Keep spoilers behind a warning.', true],
        ];

        foreach ($groups as [$name, $description, $rules, $isPrivate]) {
            $group = Group::where('name', $name)->first()
                ?? Group::factory()->create([
                    'name' => $name,
                    'description' => $description,
                    'rules' => $rules,
                    'is_private' => $isPrivate,
                    'group_language_id' => $languageIds->random(),
                ]);

            // Members: a random subset of users. The first one also owns the group.
            $memberIds = $userIds->shuffle()->take(random_int(3, $userIds->count()));

            foreach ($memberIds as $userId) {
                DB::table('user_group_subscriptions')->insertOrIgnore([
                    'user_id' => $userId,
                    'group_id' => $group->id,
                    'created_at' => now(),
                ]);
            }

            DB::table('group_moderators')->insertOrIgnore([
                'user_id' => $memberIds->first(),
                'group_id' => $group->id,
                'role' => GroupModeratorRole::Owner->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
