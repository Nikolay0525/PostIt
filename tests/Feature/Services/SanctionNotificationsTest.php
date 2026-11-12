<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Enums\ReportTargetType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationVerdictService;
use App\Services\StrikeService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A member is told of each strike, of the mute or ban it led to, and of that punishment's end.
 */
class SanctionNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $author;

    private string $versionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create(['name' => 'Cooks']);
        $this->author = User::factory()->create();
        $this->versionId = app(GroupRepositoryInterface::class)->addRuleVersion($this->group->id, [
            ['text' => 'No insults', 'example' => null, 'kind' => 'mute'],
            ['text' => 'No threats', 'example' => null, 'kind' => 'ban'],
        ])->id;
    }

    private function warn(int $rule = 0): void
    {
        $post = Post::factory()->hidden()->create(['group_id' => $this->group->id, 'user_id' => $this->author->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Post, $post->id, $this->versionId);

        app(ModerationVerdictService::class)->decide(
            $case,
            User::factory()->create()->id,
            ModerationVerdictType::Warning,
            $rule,
            [['start' => 0, 'end' => 4, 'quote' => 'word']],
            'Not acceptable.'
        );
    }

    private function types(): array
    {
        // reorder(): a user's notifications come newest first by default.
        return $this->author->notifications()->reorder('created_at')->get()->pluck('type')->all();
    }

    public function test_a_warning_tells_the_author_what_kind_it_is_and_how_close_they_are_to_a_punishment(): void
    {
        $this->warn();

        $notification = $this->author->notifications()->sole();
        $this->assertSame(NotificationType::ReceivedWarning->value, $notification->type);
        $this->assertSame($this->group->id, $notification->data['group_id']);
        $this->assertSame('mute', $notification->data['sanction']);
        $this->assertSame(1, $notification->data['count']);
        $this->assertSame(3, $notification->data['limit']);
        $this->assertNotNull($notification->data['until']);
    }

    public function test_nothing_in_it_names_the_moderator_or_the_content(): void
    {
        $this->warn();

        $data = $this->author->notifications()->sole()->data;

        $this->assertEqualsCanonicalizing(['group_id', 'moderation_verdict_id', 'sanction', 'count', 'limit', 'until', 'silent'], array_keys($data));
    }

    public function test_the_strike_that_reaches_the_limit_is_followed_by_the_mute_it_led_to(): void
    {
        $this->warn();
        $this->warn();
        $this->warn();

        // Four notifications within one second have no order of their own: what was sent is what counts.
        $types = $this->types();
        sort($types);
        $this->assertSame([
            NotificationType::GroupMute->value,
            NotificationType::ReceivedWarning->value,
            NotificationType::ReceivedWarning->value,
            NotificationType::ReceivedWarning->value,
        ], $types);

        $mute = $this->author->notifications()->where('type', NotificationType::GroupMute->value)->sole();
        $this->assertSame('mute', $mute->data['sanction']);
        $this->assertNotNull($mute->data['until']);
        $counts = $this->author->notifications()->where('type', NotificationType::ReceivedWarning->value)->get()->pluck('data.count')->sort()->values()->all();
        $this->assertSame([1, 2, 3], $counts);
    }

    public function test_a_ban_strike_ends_in_a_ban_notification(): void
    {
        $this->warn(1);
        $this->warn(1);
        $this->warn(1);

        $this->assertContains(NotificationType::GroupBan->value, $this->types());
    }

    public function test_the_end_of_a_punishment_is_announced_once(): void
    {
        $this->warn();
        $this->warn();
        $this->warn();
        $this->travel(2)->days();

        app(StrikeService::class)->settleFinished();
        app(StrikeService::class)->settleFinished();

        $this->assertSame(1, $this->author->notifications()->where('type', NotificationType::GroupUnmute->value)->count());
    }

    public function test_the_end_of_a_ban_says_the_ban_is_over(): void
    {
        $this->warn(1);
        $this->warn(1);
        $this->warn(1);
        $this->travel(2)->days();

        app(StrikeService::class)->settleFinished();

        $this->assertSame(NotificationType::GroupUnban->value, last($this->types()));
    }

    public function test_it_cannot_be_turned_off_only_silenced(): void
    {
        $this->author->settings->update(['notification_preferences' => [
            NotificationType::ReceivedWarning->value => NotificationStatus::Disabled->value,
        ]]);

        $this->warn();

        $notification = $this->author->notifications()->sole();
        $this->assertTrue($notification->data['silent']);
    }

    public function test_the_list_names_the_group_and_is_important_and_leads_to_the_group(): void
    {
        $this->warn();
        $notification = $this->author->notifications()->sole();

        $this->actingAs($this->author)->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.0.type', NotificationType::ReceivedWarning->value)
            ->assertJsonPath('notifications.0.group_name', 'Cooks')
            ->assertJsonPath('notifications.0.important', true)
            ->assertJsonPath('notifications.0.has_link', true)
            ->assertJsonPath('notifications.0.details.sanction', 'mute')
            ->assertJsonPath('notifications.0.details.count', 1)
            ->assertJsonPath('notifications.0.author', null)
            ->assertJsonMissingPath('notifications.0.details.group_id');

        $this->actingAs($this->author)->post("/notifications/{$notification->id}/open")
            ->assertRedirect(route('groups.show', $this->group->slug));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_mute_with_no_end_is_listed_with_no_date(): void
    {
        foreach ([60, 50, 40] as $ago) {
            \App\Models\Punishment::create([
                'user_id' => $this->author->id, 'group_id' => $this->group->id, 'kind' => \App\Enums\SanctionKind::Mute,
                'step' => 1, 'starts_at' => now()->subDays($ago), 'ends_at' => now()->subDays($ago - 1),
            ]);
        }

        $this->warn();
        $this->warn();
        $this->warn();

        $mute = $this->author->notifications()->where('type', NotificationType::GroupMute->value)->sole();
        $this->assertNull($mute->data['until']);
    }

    public function test_a_strike_that_ran_out_is_removed_and_its_member_told_once(): void
    {
        $this->warn();
        $this->author->notifications()->delete();
        $service = app(StrikeService::class);

        // A day is the first strike's whole life: not out yet.
        $this->assertSame(0, $service->retireExpiredStrikes());

        $this->travel(2)->days();
        $this->assertSame(1, $service->retireExpiredStrikes());
        $this->assertSame(0, $service->retireExpiredStrikes());

        $this->assertSame(0, \App\Models\Strike::count());
        $notification = $this->author->notifications()->sole();
        $this->assertSame(NotificationType::WarningRemoved->value, $notification->type);
        $this->assertSame($this->group->id, $notification->data['group_id']);
        $this->assertSame('mute', $notification->data['sanction']);
    }

    public function test_a_strike_still_in_force_is_left_alone(): void
    {
        $this->warn();
        $this->warn();
        $this->travel(2)->days();

        $this->assertSame(1, app(StrikeService::class)->retireExpiredStrikes());
        $this->assertSame(1, \App\Models\Strike::count());
    }

    public function test_the_end_of_a_punishment_clears_its_strikes_without_announcing_each(): void
    {
        $this->warn();
        $this->warn();
        $this->warn();
        $this->author->notifications()->delete();
        // The first strike has run out too, by the time the mute is over.
        $this->travel(2)->days();

        $this->artisan('moderation:settle-punishments')->assertSuccessful();

        $this->assertSame([NotificationType::GroupUnmute->value], $this->types());
    }

    public function test_the_command_reports_both_steps(): void
    {
        $this->warn();
        $this->travel(2)->days();

        $this->artisan('moderation:settle-punishments')
            ->expectsOutput('Settled 0 punishment(s).')
            ->expectsOutput('Retired 1 expired strike(s).')
            ->assertSuccessful();
    }

    public function test_the_notice_of_a_strike_running_out_is_listed_with_the_group_and_leads_to_it(): void
    {
        $this->warn();
        $this->author->notifications()->delete();
        $this->travel(2)->days();
        app(StrikeService::class)->retireExpiredStrikes();

        $this->actingAs($this->author)->getJson('/notifications')
            ->assertJsonPath('notifications.0.type', NotificationType::WarningRemoved->value)
            ->assertJsonPath('notifications.0.group_name', 'Cooks')
            ->assertJsonPath('notifications.0.important', true)
            ->assertJsonPath('notifications.0.has_link', true);
    }
}
