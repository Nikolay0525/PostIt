<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fresh posts from the last hours, so the side panel has new (unread) posts to count. Runs last,
 * so they have no votes or comments yet. Nobody has seen them: nothing writes `post_views` here.
 *
 * New means posted after the reader joined the group or began following the author. Memberships
 * and follows are seeded from a day ago (GroupSeeder, StaticAccountsSeeder), so these count. The
 * static accounts never write them: your own post is never new to you.
 *
 * Each run adds more, like PostSeeder; run it again for another batch of unread posts.
 */
class UnreadPostsSeeder extends Seeder
{
    public function run(): void
    {
        $staticIds = User::whereIn('email', StaticAccountsSeeder::emails())->pluck('id');
        $posts = collect();

        foreach (Group::with('members:id')->get() as $group) {
            $authorIds = $group->members->pluck('id')->diff($staticIds);

            if ($authorIds->isEmpty()) {
                continue;
            }

            foreach (range(1, random_int(2, 4)) as $i) {
                $posts->push($this->fresh($group->id, $authorIds->random()));
            }
        }

        // One by each person a static account follows, in a public group they're in, so the
        // "People" counters have something too (a private group's post wouldn't be visible).
        $followedIds = DB::table('user_user_subscriptions')->whereIn('user_follower_id', $staticIds)->distinct()->pluck('user_author_id');

        foreach ($followedIds as $authorId) {
            $groupId = DB::table('user_group_subscriptions')
                ->join('groups', 'groups.id', '=', 'user_group_subscriptions.group_id')
                ->where('user_group_subscriptions.user_id', $authorId)
                ->where('groups.is_private', false)
                ->inRandomOrder()
                ->value('groups.id');

            if ($groupId !== null) {
                $posts->push($this->fresh($groupId, $authorId));
            }
        }

        $this->authorsFollowTheirPosts($posts);
    }

    private function fresh(string $groupId, string $authorId): Post
    {
        $createdAt = now()->subMinutes(random_int(5, 12 * 60));

        return Post::factory()->create([
            'group_id' => $groupId,
            'user_id' => $authorId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    // Factories skip PostService, so do what FollowOwnPost does there, as PostSeeder does.
    private function authorsFollowTheirPosts(Collection $posts): void
    {
        DB::table('user_post_subscriptions')->insertOrIgnore(
            $posts->map(fn (Post $post) => ['user_id' => $post->user_id, 'post_id' => $post->id])->all()
        );
    }
}
