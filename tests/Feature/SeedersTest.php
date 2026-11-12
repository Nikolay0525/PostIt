<?php

namespace Tests\Feature;

use App\Enums\GroupModeratorRole;
use App\Enums\ModerationCaseStatus;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\Strike;
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

        // One in each of the main groups. The demo ones (SanctionSeeder) have none waiting: their cases are decided.
        $this->assertSame(Group::where('slug', 'not like', 'demo-%')->count(), Post::whereNotNull('hidden_at')->count());
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

    public function test_the_regular_account_has_strikes_a_mute_a_ban_and_the_notices_of_them(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'user@gmail.com')->first();
        $bySlug = fn (string $slug) => Group::where('slug', $slug)->first()->id;

        // Two mute strikes in one group; none of its punishments yet.
        $this->assertSame(2, Strike::where('user_id', $user->id)->where('group_id', $bySlug('demo-warned'))->count());
        $this->assertSame(0, Punishment::where('user_id', $user->id)->where('group_id', $bySlug('demo-warned'))->count());

        // A mute and a ban in force.
        $this->assertTrue(Punishment::where('user_id', $user->id)->where('group_id', $bySlug('demo-muted'))->running()->where('kind', SanctionKind::Mute->value)->exists());
        $this->assertTrue(Punishment::where('user_id', $user->id)->where('group_id', $bySlug('demo-banned'))->running()->where('kind', SanctionKind::Ban->value)->exists());

        // An ended mute and a run-out strike, both waiting for the scheduled settling.
        $this->assertFalse(Punishment::where('user_id', $user->id)->where('group_id', $bySlug('demo-ended'))->running()->exists());
        $this->assertSame(1, Punishment::where('group_id', $bySlug('demo-ended'))->whereNull('settled_at')->count());

        // Told of all of it, and of nothing that names a moderator.
        $types = $user->notifications()->pluck('type')->unique()->all();
        foreach (['received_warning', 'group_mute', 'group_ban'] as $type) {
            $this->assertContains($type, $types);
        }
        $this->assertSame(0, Post::where('user_id', $user->id)->whereNotNull('hidden_at')->whereIn('group_id', Group::where('slug', 'like', 'demo-%')->pluck('id'))->count());
        $this->assertGreaterThan(0, Post::where('user_id', $user->id)->whereNotNull('removed_at')->count());
    }

    public function test_settling_what_the_seeded_account_left_waiting_ends_the_mute_and_the_strike(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'user@gmail.com')->first();
        $user->notifications()->delete();

        $this->artisan('moderation:settle-punishments')->assertSuccessful();

        $types = $user->notifications()->pluck('type')->all();
        $this->assertContains('group_unmute', $types);
        $this->assertContains('warning_removed', $types);
    }

    public function test_seeding_again_gives_the_account_no_second_set(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'user@gmail.com')->first();
        $strikes = Strike::where('user_id', $user->id)->count();

        $this->seed(\Database\Seeders\SanctionSeeder::class);

        $this->assertSame($strikes, Strike::where('user_id', $user->id)->count());
    }
}
