<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Models\Group;
use App\Models\ModerationVerdict;
use App\Models\PlatformRuleVersion;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\Strike;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrikeModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    private function warningVerdict(Group $group): ModerationVerdict
    {
        $post = Post::factory()->hidden()->create(['group_id' => $group->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);

        return $case->verdict()->create(['type' => ModerationVerdictType::Warning]);
    }

    public function test_a_rule_has_the_kind_its_owner_gave_it_and_one_without_counts_as_a_mute(): void
    {
        $group = Group::factory()->create();
        $version = app(GroupRepositoryInterface::class)->addRuleVersion($group->id, [
            ['text' => 'No insults', 'example' => null, 'kind' => 'mute'],
            ['text' => 'No threats', 'example' => null, 'kind' => 'ban'],
            ['text' => 'Written before kinds existed', 'example' => null],
        ]);

        $this->assertSame(SanctionKind::Mute, $version->kindOfRule(0));
        $this->assertSame(SanctionKind::Ban, $version->kindOfRule(1));
        $this->assertSame(SanctionKind::Mute, $version->kindOfRule(2));
        $this->assertNull($version->kindOfRule(9));
    }

    public function test_platform_rules_are_kept_in_versions_with_kinds(): void
    {
        $version = PlatformRuleVersion::create(['rules' => [['text' => 'No spam', 'example' => null, 'kind' => 'ban']]]);

        $this->assertSame(SanctionKind::Ban, $version->fresh()->kindOfRule(0));
    }

    public function test_a_strike_comes_from_a_verdict_and_belongs_to_a_user_in_a_group(): void
    {
        $group = Group::factory()->create();
        $verdict = $this->warningVerdict($group);
        $user = User::factory()->create();

        $strike = Strike::create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'kind' => SanctionKind::Mute,
            'moderation_verdict_id' => $verdict->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame(SanctionKind::Mute, $strike->fresh()->kind);
        $this->assertSame($verdict->id, $strike->verdict->id);
        $this->assertSame($strike->id, $verdict->strike->id);
        $this->assertTrue($strike->expires_at->isFuture());
    }

    public function test_a_verdict_gives_at_most_one_strike(): void
    {
        $group = Group::factory()->create();
        $verdict = $this->warningVerdict($group);
        $row = ['user_id' => User::factory()->create()->id, 'group_id' => $group->id, 'kind' => SanctionKind::Mute, 'moderation_verdict_id' => $verdict->id, 'expires_at' => now()->addDay()];
        Strike::create($row);

        $this->expectException(UniqueConstraintViolationException::class);

        Strike::create($row);
    }

    public function test_a_strike_may_belong_to_the_account_rather_than_a_group(): void
    {
        $verdict = $this->warningVerdict(Group::factory()->create());

        $strike = Strike::create([
            'user_id' => User::factory()->create()->id,
            'group_id' => null,
            'kind' => SanctionKind::Ban,
            'moderation_verdict_id' => $verdict->id,
            'expires_at' => now()->addWeek(),
        ]);

        $this->assertNull($strike->fresh()->group_id);
    }

    public function test_a_punishment_remembers_its_step_its_end_and_the_verdicts_it_was_made_of(): void
    {
        $group = Group::factory()->create();
        $first = $this->warningVerdict($group);
        $second = $this->warningVerdict($group);

        $punishment = Punishment::create([
            'user_id' => User::factory()->create()->id,
            'group_id' => $group->id,
            'kind' => SanctionKind::Ban,
            'step' => 2,
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $punishment->verdicts()->attach([$first->id, $second->id]);

        $punishment = $punishment->fresh();
        $this->assertSame(2, $punishment->step);
        $this->assertSame(SanctionKind::Ban, $punishment->kind);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $punishment->verdicts->pluck('id')->all());
    }

    public function test_a_punishment_is_running_until_it_ends_or_is_lifted_and_a_permanent_one_has_no_end(): void
    {
        $base = ['user_id' => User::factory()->create()->id, 'group_id' => null, 'kind' => SanctionKind::Mute, 'step' => 1, 'starts_at' => now()->subDay()];

        $this->assertTrue(Punishment::create($base + ['ends_at' => now()->addHour()])->isRunning());
        $this->assertFalse(Punishment::create($base + ['ends_at' => now()->subHour()])->isRunning());
        $this->assertFalse(Punishment::create($base + ['ends_at' => now()->addHour(), 'lifted_at' => now()])->isRunning());
        $this->assertTrue(Punishment::create($base + ['ends_at' => null])->isRunning());
    }

    public function test_a_group_has_no_strike_limits_of_its_own_until_the_owner_lowers_them(): void
    {
        $group = Group::factory()->create();
        $this->assertNull($group->fresh()->mute_strike_limit);
        $this->assertNull($group->fresh()->ban_strike_limit);

        $group->update(['mute_strike_limit' => 2, 'ban_strike_limit' => 3]);

        $this->assertSame(2, $group->fresh()->mute_strike_limit);
        $this->assertSame(3, $group->fresh()->ban_strike_limit);
    }
}
