<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Models\Group;
use App\Models\ModerationVerdict;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationVerdictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    private function caseWithRule(): array
    {
        $group = Group::factory()->create();
        $version = app(GroupRepositoryInterface::class)->addRuleVersion($group->id, [['text' => 'Be kind', 'example' => null]]);
        $post = Post::factory()->hidden()->create(['group_id' => $group->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);

        return [$case, $version];
    }

    public function test_a_verdict_keeps_the_rule_the_marks_and_the_reason_of_a_case(): void
    {
        [$case, $version] = $this->caseWithRule();
        $moderator = User::factory()->create();

        $case->verdict()->create([
            'type' => ModerationVerdictType::Warning,
            'decided_by' => $moderator->id,
            'rule_version_id' => $version->id,
            'rule_index' => 0,
            'marks' => [['start' => 4, 'end' => 9, 'quote' => 'stupid']],
            'reason' => 'This is an insult, not an argument.',
            'warning_days' => 30,
        ]);

        $verdict = $case->fresh()->verdict;
        $this->assertSame(ModerationVerdictType::Warning, $verdict->type);
        $this->assertSame('stupid', $verdict->marks[0]['quote']);
        $this->assertSame(30, $verdict->warning_days);
        $this->assertSame($version->id, $verdict->ruleVersion->id);
        $this->assertSame($moderator->id, $verdict->decider->id);
    }

    public function test_who_decided_is_never_part_of_a_serialized_verdict(): void
    {
        [$case] = $this->caseWithRule();
        $case->verdict()->create(['type' => ModerationVerdictType::NoViolation, 'decided_by' => User::factory()->create()->id]);

        $this->assertArrayNotHasKey('decided_by', $case->fresh()->verdict->toArray());
    }

    public function test_a_case_is_decided_once(): void
    {
        [$case] = $this->caseWithRule();
        $case->verdict()->create(['type' => ModerationVerdictType::NoViolation]);

        $this->expectException(UniqueConstraintViolationException::class);

        ModerationVerdict::create(['moderation_case_id' => $case->id, 'type' => ModerationVerdictType::Remove]);
    }

    public function test_only_a_punishing_verdict_must_say_where_the_rule_was_broken(): void
    {
        $this->assertFalse(ModerationVerdictType::NoViolation->punishes());
        $this->assertTrue(ModerationVerdictType::Remove->punishes());
        $this->assertTrue(ModerationVerdictType::Warning->punishes());
    }
}
