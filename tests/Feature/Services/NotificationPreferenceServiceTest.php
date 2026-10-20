<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use App\Services\NotificationPreferenceService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationPreferenceService $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->preferences = $this->app->make(NotificationPreferenceService::class);
    }

    public function test_a_kind_without_a_setting_is_enabled(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            NotificationStatus::Enabled,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost),
        );
    }

    public function test_a_kind_uses_its_saved_status(): void
    {
        $user = $this->userWith(['notification_preferences' => [
            NotificationType::NewCommentUnderPost->value => NotificationStatus::Disabled->value,
            NotificationType::NewCommentAnswer->value => NotificationStatus::Muted->value,
        ]]);

        $this->assertSame(NotificationStatus::Disabled, $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost));
        $this->assertSame(NotificationStatus::Muted, $this->preferences->statusFor($user, NotificationType::NewCommentAnswer));
        $this->assertSame(NotificationStatus::Enabled, $this->preferences->statusFor($user, NotificationType::NewFollower));
    }

    public function test_an_important_kind_cannot_be_turned_off_only_silenced(): void
    {
        $user = $this->userWith(['notification_preferences' => [
            NotificationType::PlatformBan->value => NotificationStatus::Disabled->value,
        ]]);

        $this->assertSame(NotificationStatus::Muted, $this->preferences->statusFor($user, NotificationType::PlatformBan));
    }

    public function test_muting_forever_silences_enabled_kinds(): void
    {
        $user = $this->userWith(['muted_forever' => true]);

        $this->assertSame(NotificationStatus::Muted, $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost));
        $this->assertSame(NotificationStatus::Muted, $this->preferences->statusFor($user, NotificationType::PlatformBan));
    }

    public function test_muting_until_a_future_time_silences_and_a_past_one_does_not(): void
    {
        $muted = $this->userWith(['muted_until' => now()->addHour()]);
        $expired = $this->userWith(['muted_until' => now()->subMinute()]);

        $this->assertSame(NotificationStatus::Muted, $this->preferences->statusFor($muted, NotificationType::NewCommentUnderPost));
        $this->assertSame(NotificationStatus::Enabled, $this->preferences->statusFor($expired, NotificationType::NewCommentUnderPost));
    }

    public function test_muting_everything_does_not_turn_a_disabled_kind_back_on(): void
    {
        $user = $this->userWith([
            'muted_forever' => true,
            'notification_preferences' => [
                NotificationType::NewCommentUnderPost->value => NotificationStatus::Disabled->value,
            ],
        ]);

        $this->assertSame(NotificationStatus::Disabled, $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost));
    }

    public function test_a_disabled_kind_is_not_stored_and_a_muted_one_is_stored_silent(): void
    {
        $post = Post::factory()->create();
        $post->author->settings->update(['notification_preferences' => [
            NotificationType::NewCommentUnderPost->value => NotificationStatus::Disabled->value,
        ]]);
        $commenter = User::factory()->create();
        $comments = $this->app->make(CommentService::class);

        $comments->createComment($post->id, $commenter->id, 'Nobody hears this');
        $this->assertCount(0, $post->author->notifications);

        $post->author->settings->update(['notification_preferences' => [
            NotificationType::NewCommentUnderPost->value => NotificationStatus::Muted->value,
        ]]);
        $comments->createComment($post->id, $commenter->id, 'Quietly');

        $notifications = $post->author->fresh()->notifications;
        $this->assertCount(1, $notifications);
        $this->assertTrue($notifications[0]->data['silent']);
    }

    public function test_a_muted_group_silences_its_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $other = Post::factory()->create();

        $this->preferences->muteGroup($user->id, $post->group_id);

        $this->assertSame(
            NotificationStatus::Muted,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $post->group_id, $post->id),
        );
        $this->assertSame(
            NotificationStatus::Enabled,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $other->group_id, $other->id),
        );
    }

    public function test_a_muted_post_silences_only_itself(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $sibling = Post::factory()->create(['group_id' => $post->group_id]);

        $this->preferences->mutePost($user->id, $post->id);

        $this->assertSame(
            NotificationStatus::Muted,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $post->group_id, $post->id),
        );
        $this->assertSame(
            NotificationStatus::Enabled,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $sibling->group_id, $sibling->id),
        );
    }

    public function test_muting_twice_and_unmuting(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->preferences->muteGroup($user->id, $post->group_id);
        $this->preferences->muteGroup($user->id, $post->group_id);
        $this->preferences->unmuteGroup($user->id, $post->group_id);

        $this->assertSame(
            NotificationStatus::Enabled,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $post->group_id, $post->id),
        );
    }

    public function test_a_muted_source_does_not_turn_a_disabled_kind_back_on(): void
    {
        $user = $this->userWith(['notification_preferences' => [
            NotificationType::NewCommentUnderPost->value => NotificationStatus::Disabled->value,
        ]]);
        $post = Post::factory()->create();

        $this->preferences->mutePost($user->id, $post->id);

        $this->assertSame(
            NotificationStatus::Disabled,
            $this->preferences->statusFor($user, NotificationType::NewCommentUnderPost, $post->group_id, $post->id),
        );
    }

    public function test_a_comment_in_a_muted_group_arrives_silent(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();
        $this->preferences->muteGroup($post->user_id, $post->group_id);

        $this->app->make(CommentService::class)->createComment($post->id, $commenter->id, 'Shh');

        $this->assertTrue($post->author->notifications[0]->data['silent']);
    }

    private function userWith(array $settings): User
    {
        $user = User::factory()->create();
        $user->settings->update($settings);

        return $user;
    }
}
