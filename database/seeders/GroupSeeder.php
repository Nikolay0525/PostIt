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
        $languageCodes = SpeakingLanguage::pluck('code');
        $userIds = User::pluck('id');

        $groups = [
            ['Laravel Developers', 'laravel', 'Tips, questions and showcases for people building with Laravel.', [
                ['Be kind.', null],
                ['No spam.', 'Posting the same link to your course in several threads.'],
                ['Put the Laravel version in questions.', '"[Laravel 13] Queue job fails silently on Redis"'],
            ], false],
            ['Hiking & Trails', 'hiking', 'Routes, photos and gear advice from people who love the outdoors.', [
                ['Share your route details.', 'Start point, distance, elevation gain and how long it took you.'],
                ['Leave no trace.', null],
            ], false],
            ['Home Cooking', 'home-cooking', 'Recipes and kitchen tricks from everyday cooks.', [
                ['Credit the original author.', '"Adapted from Grandma\'s notebook" or a link to the source recipe.'],
                ['No ads.', null],
            ], false],
            ['Retro Gaming', 'retro-gaming', 'Old consoles, cartridges and the games we still love.', [
                ['No piracy links.', 'ROM download sites, torrent magnets or "DM me for the file".'],
            ], false],
            ['Private Book Club', 'book-club', 'A small invite-only group for monthly book discussions.', [
                ['Keep spoilers behind a warning.', 'Start the post with "SPOILERS (ch. 1–12)" before discussing the plot.'],
            ], true],
        ];

        foreach ($groups as [$name, $slug, $description, $rules, $isPrivate]) {
            $group = Group::where('slug', $slug)->first()
                ?? Group::factory()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_private' => $isPrivate,
                    'language_code' => $languageCodes->random(),
                ]);

            if (! $group->ruleVersions()->exists()) {
                $group->ruleVersions()->create([
                    'rules' => array_map(fn (array $rule) => ['text' => $rule[0], 'example' => $rule[1]], $rules),
                ]);
            }

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
