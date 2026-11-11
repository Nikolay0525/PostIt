<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Models\Group;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\CommentService;
use App\Services\ModerationVerdictService;
use App\Services\PostService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A ban closes a group's content to the banned: they are not told about its comments, and a
 * notification about a post they can't see does not show its title.
 */
class BannedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create();
        $this->post = app(PostService::class)->createPost($this->group->id, User::factory()->create()->id, 'A secret title', 'Some text');
    }

    private function ban(User $user, ?Group $group = null): void
    {
        Punishment::create([
            'user_id' => $user->id,
            'group_id' => ($group ?? $this->group)->id,
            'kind' => SanctionKind::Ban,
            'step' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    private function comment(User $by, ?string $parentId = null)
    {
        return app(CommentService::class)->createComment($this->post->id, $by->id, 'Hello', $parentId);
    }

    public function test_a_banned_follower_is_not_notified_about_a_new_comment(): void
    {
        $this->ban($this->post->author);

        $this->comment(User::factory()->create());

        $this->assertCount(0, $this->post->author->notifications);
    }

    public function test_a_follower_banned_from_another_group_still_is(): void
    {
        $this->ban($this->post->author, Group::factory()->create());

        $this->comment(User::factory()->create());

        $this->assertCount(1, $this->post->author->notifications);
    }

    public function test_a_banned_author_of_the_answered_comment_is_not_told_of_the_answer_nor_as_a_follower(): void
    {
        $parent = $this->comment($this->post->author);
        $this->post->author->notifications()->delete();
        $this->ban($this->post->author);

        $this->comment(User::factory()->create(), $parent->id);

        $this->assertCount(0, $this->post->author->notifications()->get());
    }

    public function test_a_notification_about_a_post_in_a_group_the_user_is_banned_from_hides_its_title(): void
    {
        $this->comment(User::factory()->create());
        $this->ban($this->post->author);

        $this->actingAs($this->post->author)->getJson('/notifications')
            ->assertJsonPath('notifications.0.post_title', null)
            ->assertJsonPath('notifications.0.post_unavailable', true)
            ->assertJsonPath('notifications.0.has_link', false);
    }

    public function test_a_notification_about_a_post_under_moderation_or_removed_hides_its_title_too(): void
    {
        $this->comment(User::factory()->create());
        $notification = fn () => $this->actingAs($this->post->author)->getJson('/notifications');

        $notification()->assertJsonPath('notifications.0.post_title', 'A secret title');

        $this->post->update(['hidden_at' => now()]);
        $notification()
            ->assertJsonPath('notifications.0.post_title', null)
            ->assertJsonPath('notifications.0.post_unavailable', true);

        $this->post->update(['hidden_at' => null, 'removed_at' => now()]);
        $notification()
            ->assertJsonPath('notifications.0.post_title', null)
            ->assertJsonPath('notifications.0.post_unavailable', true);
    }

    public function test_a_deleted_post_is_still_said_to_be_deleted_not_unavailable(): void
    {
        $this->comment(User::factory()->create());
        $this->post->update(['is_deleted' => true, 'deleted_at' => now()]);

        $this->actingAs($this->post->author)->getJson('/notifications')
            ->assertJsonPath('notifications.0.post_title', null)
            ->assertJsonPath('notifications.0.post_unavailable', false);
    }

    public function test_opening_such_a_notification_leads_nowhere_but_marks_it_read(): void
    {
        $this->comment(User::factory()->create());
        $this->ban($this->post->author);
        $notification = $this->post->author->notifications()->first();

        $this->actingAs($this->post->author)->from('/')->post("/notifications/{$notification->id}/open")->assertRedirect('/');

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
