<?php

namespace Tests\Feature\Services;

use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What the moderation queue reads: the group's open cases, the longest waiting first, with the
 * content and the reports — and without anyone's identity.
 */
class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private ModerationCaseRepositoryInterface $cases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->cases = app(ModerationCaseRepositoryInterface::class);
    }

    public function test_the_queue_has_the_groups_open_cases_oldest_first(): void
    {
        $group = Group::factory()->create();
        $newer = $this->cases->open($group->id, ReportTargetType::Post, Post::factory()->hidden()->create(['group_id' => $group->id])->id);
        $older = $this->cases->open($group->id, ReportTargetType::Post, Post::factory()->hidden()->create(['group_id' => $group->id])->id);
        $older->forceFill(['created_at' => now()->subDay()])->save();
        // Another group's case and a closed one are not in it.
        $other = Group::factory()->create();
        $this->cases->open($other->id, ReportTargetType::Post, Post::factory()->create(['group_id' => $other->id])->id);
        $closed = $this->cases->open($group->id, ReportTargetType::Post, Post::factory()->create(['group_id' => $group->id])->id);
        $closed->forceFill(['status' => 1, 'open_key' => null])->save();

        $page = $this->cases->openInGroup($group->id, 10);

        $this->assertSame([$older->id, $newer->id], $page->pluck('id')->all());
    }

    public function test_a_case_carries_its_content_and_its_reports_without_the_authors_or_reporters_ids(): void
    {
        $post = Post::factory()->hidden()->create();
        $comment = Comment::factory()->hidden()->create();
        $postCase = $this->cases->open($post->group_id, ReportTargetType::Post, $post->id);
        $commentCase = $this->cases->open($post->group_id, ReportTargetType::Comment, $comment->id);
        $reports = app(ReportRepositoryInterface::class);
        foreach (User::factory()->count(2)->create() as $reporter) {
            $reports->create($reporter->id, ReportTargetType::Post, $post->id, $post->group_id, null, null, false, 'Spam');
        }
        $reports->attachToCase(ReportTargetType::Post, $post->id, $postCase->id);

        $page = $this->cases->openInGroup($post->group_id, 10)->keyBy('id');

        $found = $page->get($postCase->id);
        $this->assertSame(2, $found->reports_count);
        $this->assertCount(2, $found->reports);
        $this->assertSame($post->id, $found->target->id);
        $this->assertNull($found->target->user_id);
        $this->assertNull($found->reports->first()->reporter_id);

        $this->assertSame($comment->id, $page->get($commentCase->id)->target->id);
        $this->assertNull($page->get($commentCase->id)->target->user_id);
    }

    public function test_one_case_can_be_found_for_moderation(): void
    {
        $post = Post::factory()->hidden()->create();
        $case = $this->cases->open($post->group_id, ReportTargetType::Post, $post->id);

        $found = $this->cases->findForModeration($case->id);

        $this->assertSame($post->id, $found->target->id);
        $this->assertNull($this->cases->findForModeration((string) Str::uuid()));
    }
}
