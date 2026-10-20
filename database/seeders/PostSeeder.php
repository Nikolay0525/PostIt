<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Group::with('members:id')->get() as $group) {
            if ($group->members->isEmpty()) {
                continue;
            }

            // Authors are picked from the group's own members.
            $author = fn () => ['user_id' => $group->members->random()->id];

            Post::factory()->count(3)->for($group)->state($author)->create();
            Post::factory()->withoutTitle()->for($group)->state($author)->create();
        }

        // Factories skip PostService, so do what FollowOwnPost does there: authors follow their posts.
        DB::table('user_post_subscriptions')->insertOrIgnore(
            Post::query()->get(['id', 'user_id'])
                ->map(fn (Post $post) => ['user_id' => $post->user_id, 'post_id' => $post->id])
                ->all()
        );
    }
}
