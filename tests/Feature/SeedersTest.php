<?php

namespace Tests\Feature;

use App\Enums\GroupModeratorRole;
use App\Enums\ModerationCaseStatus;
use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeded_data_has_hidden_posts_and_each_has_its_open_case(): void
    {
        $this->seed(DatabaseSeeder::class);

        $hiddenPosts = Post::whereNotNull('hidden_at')->get();
        $this->assertGreaterThan(0, $hiddenPosts->count());
        // Not every post: it is an example, not the feed.
        $this->assertLessThan(Post::count(), $hiddenPosts->count());

        foreach ($hiddenPosts as $post) {
            $case = ModerationCase::where('target_id', $post->id)->first();
            $this->assertNotNull($case, "A hidden post has a case ({$post->id}).");
            $this->assertSame(ReportTargetType::Post, $case->target_type);
            $this->assertSame(ModerationCaseStatus::Open, $case->status);
            $this->assertSame($post->group_id, $case->group_id);
        }
    }

    public function test_every_hidden_comment_has_an_open_case_too(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Comment::whereNotNull('hidden_at')->with('post')->get() as $comment) {
            $this->assertFalse($comment->is_deleted, 'A comment is hidden or deleted, not both.');
            $this->assertSame(
                1,
                ModerationCase::where('target_type', ReportTargetType::Comment->value)
                    ->where('target_id', $comment->id)
                    ->where('group_id', $comment->post->group_id)
                    ->count()
            );
        }

        $this->assertSame(Group::count(), Post::whereNotNull('hidden_at')->count());
    }

    public function test_the_static_user_owns_one_group_and_moderates_two_and_every_group_keeps_one_owner(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->assertSame(1, GroupModerator::where('user_id', $user->id)->where('role', GroupModeratorRole::Owner->value)->count());
        $this->assertSame(2, GroupModerator::where('user_id', $user->id)->where('role', GroupModeratorRole::Moderator->value)->count());

        foreach (Group::all() as $group) {
            $this->assertSame(1, GroupModerator::where('group_id', $group->id)->where('role', GroupModeratorRole::Owner->value)->count());
        }
    }
}
