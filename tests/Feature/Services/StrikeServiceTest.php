<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Events\PunishmentGiven;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationVerdict;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\Strike;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationVerdictService;
use App\Services\StrikeService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * A "Warning" verdict gives a strike, and strikes add up to a punishment, with nobody deciding. The
 * platform's numbers are the starting ones of config/moderation.php: a strike lasts 1, 7, then 30
 * days; three of a kind punish; a punishment lasts 1, 7, 30 days, then forever.
 */
class StrikeServiceTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $author;

    private User $moderator;

    private string $versionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create();
        $this->author = User::factory()->create();
        $this->moderator = User::factory()->create();
        $this->versionId = app(GroupRepositoryInterface::class)->addRuleVersion($this->group->id, [
            ['text' => 'No insults', 'example' => null, 'kind' => 'mute'],
            ['text' => 'No threats', 'example' => null, 'kind' => 'ban'],
        ])->id;
    }

    /** Warns the author for a new post of theirs, under the rule with this index (0 mutes, 1 bans). */
    private function warn(int $rule = 0, ?User $author = null, ?Group $group = null): ModerationVerdict
    {
        $group ??= $this->group;
        $post = Post::factory()->hidden()->create(['group_id' => $group->id, 'user_id' => ($author ?? $this->author)->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id, $group->is($this->group) ? $this->versionId : null);

        return app(ModerationVerdictService::class)->decide(
            $case,
            $this->moderator->id,
            ModerationVerdictType::Warning,
            $rule,
            [['start' => 0, 'end' => 4, 'quote' => 'word']],
            'Not acceptable.'
        );
    }

    private function strikes(?User $user = null, ?SanctionKind $kind = null)
    {
        return Strike::where('user_id', ($user ?? $this->author)->id)
            ->when($kind, fn ($q) => $q->where('kind', $kind->value))
            ->orderBy('expires_at')
            ->get();
    }

    public function test_a_warning_gives_the_author_a_strike_of_the_kind_of_the_rule_it_names(): void
    {
        $this->warn(0);
        $this->warn(1);

        $this->assertCount(1, $this->strikes(kind: SanctionKind::Mute));
        $this->assertCount(1, $this->strikes(kind: SanctionKind::Ban));
        $this->assertSame($this->group->id, $this->strikes()->first()->group_id);
    }

    public function test_the_other_verdicts_give_no_strike(): void
    {
        $service = app(ModerationVerdictService::class);
        $cases = app(ModerationCaseRepositoryInterface::class);
        $marks = [['start' => 0, 'end' => 4, 'quote' => 'word']];

        foreach ([ModerationVerdictType::NoViolation, ModerationVerdictType::Remove] as $type) {
            $post = Post::factory()->hidden()->create(['group_id' => $this->group->id, 'user_id' => $this->author->id]);
            $case = $cases->open($this->group->id, ReportTargetType::Post, $post->id, $this->versionId);
            $service->decide($case, $this->moderator->id, $type, 0, $marks, 'Reason.');
        }

        $this->assertCount(0, $this->strikes());
    }

    public function test_a_warning_on_a_comment_gives_its_author_a_strike_too(): void
    {
        $comment = Comment::factory()->hidden()->create(['user_id' => $this->author->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Comment, $comment->id, $this->versionId);

        app(ModerationVerdictService::class)->decide($case, $this->moderator->id, ModerationVerdictType::Warning, 0, [['start' => 0, 'end' => 4, 'quote' => 'word']], 'Rude.');

        $this->assertCount(1, $this->strikes());
    }

    public function test_the_more_strikes_a_member_has_the_longer_a_new_one_lasts_and_the_earlier_ones_stay(): void
    {
        $this->warn();
        $this->warn();

        [$first, $second] = $this->strikes()->all();

        $this->assertEqualsWithDelta(1, now()->diffInDays($first->expires_at, false), 0.01);
        $this->assertEqualsWithDelta(7, now()->diffInDays($second->expires_at, false), 0.01);
    }

    public function test_a_strike_that_has_run_out_no_longer_counts(): void
    {
        $this->warn();
        $this->travel(2)->days();
        // The first is gone: these are the first and the second strike again, and no limit is reached.
        $this->warn();
        $this->warn();

        $this->assertSame(0, Punishment::count());
    }

    public function test_reaching_the_limit_punishes_at_once_without_anyone_deciding(): void
    {
        Event::fake([PunishmentGiven::class]);

        $verdicts = [$this->warn(), $this->warn(), $this->warn()];

        $punishment = Punishment::sole();
        $this->assertSame($this->author->id, $punishment->user_id);
        $this->assertSame($this->group->id, $punishment->group_id);
        $this->assertSame(SanctionKind::Mute, $punishment->kind);
        $this->assertSame(1, $punishment->step);
        $this->assertEqualsWithDelta(1, now()->diffInDays($punishment->ends_at, false), 0.01);
        $this->assertEqualsCanonicalizing(collect($verdicts)->pluck('id')->all(), $punishment->verdicts->pluck('id')->all());
        Event::assertDispatchedTimes(PunishmentGiven::class, 1);
    }

    public function test_a_ban_strike_leads_to_a_ban(): void
    {
        $this->warn(1);
        $this->warn(1);
        $this->warn(1);

        $this->assertSame(SanctionKind::Ban, Punishment::sole()->kind);
    }

    public function test_mute_strikes_and_ban_strikes_are_counted_apart(): void
    {
        $this->warn(0);
        $this->warn(0);
        $this->warn(1);
        $this->warn(1);

        $this->assertSame(0, Punishment::count());

        $this->warn(1);

        $this->assertSame([SanctionKind::Ban], Punishment::all()->pluck('kind')->all());
    }

    public function test_a_member_being_punished_is_not_punished_again_for_strikes_that_come_in_meanwhile(): void
    {
        foreach (range(1, 5) as $i) {
            $this->warn();
        }

        $this->assertSame(1, Punishment::count());
    }

    public function test_strikes_in_another_group_or_of_another_member_are_not_added_up(): void
    {
        $other = Group::factory()->create();
        $app = app(GroupRepositoryInterface::class);
        $app->addRuleVersion($other->id, [['text' => 'Be kind', 'example' => null, 'kind' => 'mute']]);

        $this->warn();
        $this->warn();
        $this->warn(author: User::factory()->create());

        $this->assertSame(0, Punishment::count());
    }

    public function test_an_owner_can_lower_the_limit_for_the_group_but_not_raise_it(): void
    {
        $service = app(StrikeService::class);
        $this->assertSame(3, $service->limitFor($this->group, SanctionKind::Mute));

        $this->group->update(['mute_strike_limit' => 2, 'ban_strike_limit' => 10]);
        $group = $this->group->fresh();

        $this->assertSame(2, $service->limitFor($group, SanctionKind::Mute));
        $this->assertSame(3, $service->limitFor($group, SanctionKind::Ban));

        $this->warn();
        $this->warn();

        $this->assertSame(1, Punishment::count());
    }

    public function test_a_repeated_punishment_is_longer_but_only_while_the_earlier_ones_are_remembered(): void
    {
        $base = ['user_id' => $this->author->id, 'group_id' => $this->group->id, 'kind' => SanctionKind::Mute, 'step' => 1, 'ends_at' => now()->subDays(20)];
        // One within the memory period, one long forgotten, one an appeal took back: only the first counts.
        Punishment::create($base + ['starts_at' => now()->subDays(30)]);
        Punishment::create($base + ['starts_at' => now()->subDays(400)]);
        Punishment::create($base + ['starts_at' => now()->subDays(40), 'lifted_at' => now()->subDays(35)]);

        $this->warn();
        $this->warn();
        $this->warn();

        $latest = Punishment::orderByDesc('starts_at')->first();
        $this->assertSame(2, $latest->step);
        $this->assertEqualsWithDelta(7, now()->diffInDays($latest->ends_at, false), 0.01);
    }

    public function test_the_last_rung_of_the_punishments_is_permanent(): void
    {
        $base = ['user_id' => $this->author->id, 'group_id' => $this->group->id, 'kind' => SanctionKind::Mute, 'step' => 1, 'ends_at' => now()->subDays(20)];
        foreach ([60, 50, 40] as $ago) {
            Punishment::create($base + ['starts_at' => now()->subDays($ago)]);
        }

        $this->warn();
        $this->warn();
        $this->warn();

        $latest = Punishment::orderByDesc('starts_at')->first();
        $this->assertSame(4, $latest->step);
        $this->assertNull($latest->ends_at);
        $this->assertTrue($latest->isRunning());
    }

    /** Three mute strikes: a one-day mute, over after two days. */
    private function punishedAndServed(): Punishment
    {
        $this->warn();
        $this->warn();
        $this->warn();
        $punishment = Punishment::sole();
        $this->travel(2)->days();

        return $punishment;
    }

    public function test_when_a_punishment_has_run_its_course_the_strikes_are_deleted_and_the_member_starts_over(): void
    {
        $service = app(StrikeService::class);
        $punishment = $this->punishedAndServed();
        $this->travelBack();

        // Not over yet: nothing is touched.
        $this->assertSame(0, $service->settleFinished());
        $this->assertCount(3, $this->strikes());

        $this->travel(2)->days();
        $this->assertSame(1, $service->settleFinished());
        $this->assertCount(0, $this->strikes());
        $this->assertNotNull($punishment->fresh()->settled_at);

        // Once only.
        $this->assertSame(0, $service->settleFinished());
    }

    public function test_only_the_strikes_of_the_punished_kind_are_deleted(): void
    {
        $this->warn(1);
        $this->punishedAndServed();

        app(StrikeService::class)->settleFinished();

        $this->assertCount(1, $this->strikes(kind: SanctionKind::Ban));
        $this->assertCount(0, $this->strikes(kind: SanctionKind::Mute));
    }

    public function test_a_new_strike_after_the_punishment_ended_starts_the_count_over_without_waiting_for_the_schedule(): void
    {
        $this->punishedAndServed();

        // The old strikes would still count, and a third would punish again, if it were not settled first.
        $this->warn();

        $this->assertSame(1, Punishment::count());
        $this->assertCount(1, $this->strikes());
    }

    public function test_a_punishment_taken_back_by_an_appeal_or_a_permanent_one_is_not_settled(): void
    {
        $base = ['user_id' => $this->author->id, 'group_id' => $this->group->id, 'kind' => SanctionKind::Mute, 'step' => 1, 'starts_at' => now()->subDays(5)];
        Punishment::create($base + ['ends_at' => now()->subDays(2), 'lifted_at' => now()->subDays(3)]);
        Punishment::create($base + ['ends_at' => null]);

        $this->assertSame(0, app(StrikeService::class)->settleFinished());
    }

    public function test_the_scheduled_command_settles_every_member(): void
    {
        $this->punishedAndServed();

        $this->artisan('moderation:settle-punishments')->expectsOutput('Settled 1 punishment(s).')->assertSuccessful();

        $this->assertCount(0, $this->strikes());
    }
}
