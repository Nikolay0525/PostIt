<?php

namespace Tests\Feature\Http;

use App\Enums\AppealStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Events\AppealFiled;
use App\Models\Appeal;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationVerdict;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationVerdictService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * An author appeals a verdict that punished: within 7 days, once, with a short explanation. Filing
 * changes nothing else: the content stays removed until a jury decides.
 */
class AppealTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $author;

    private Post $post;

    private ModerationVerdict $verdict;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create(['is_private' => false]);
        $this->author = User::factory()->create();
        $this->post = Post::factory()->create(['group_id' => $this->group->id, 'user_id' => $this->author->id, 'article' => 'You are a complete idiot']);
        $this->verdict = $this->decide($this->post, ModerationVerdictType::Remove);
    }

    private function decide(Post|Comment $content, ModerationVerdictType $type): ModerationVerdict
    {
        $versionId = $this->group->ruleVersions()->latest('created_at')->value('id')
            ?? app(GroupRepositoryInterface::class)->addRuleVersion($this->group->id, [['text' => 'Be respectful', 'example' => null, 'kind' => 'mute']])->id;
        $case = app(ModerationCaseRepositoryInterface::class)->open(
            $this->group->id,
            $content instanceof Post ? ReportTargetType::Post : ReportTargetType::Comment,
            $content->id,
            $versionId,
        );

        return app(ModerationVerdictService::class)->decide(
            $case,
            User::factory()->create()->id,
            $type,
            $type->punishes() ? 0 : null,
            $type->punishes() ? [['start' => 8, 'end' => 22, 'quote' => 'complete idiot']] : null,
            $type->punishes() ? 'An insult.' : null,
        );
    }

    private function appeal(?User $by = null, ?ModerationVerdict $verdict = null, string $statement = 'It was a quote from a film.')
    {
        return $this->actingAs($by ?? $this->author)->postJson(route('appeals.store', ($verdict ?? $this->verdict)->id), ['statement' => $statement]);
    }

    public function test_the_author_appeals_and_it_waits_for_the_jury_while_the_content_stays_removed(): void
    {
        Event::fake([AppealFiled::class]);

        $this->appeal(statement: '  It was a quote from a film.  ')
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('statement', 'It was a quote from a film.');

        $appeal = Appeal::sole();
        $this->assertSame($this->verdict->id, $appeal->moderation_verdict_id);
        $this->assertSame($this->author->id, $appeal->appellant_id);
        $this->assertSame(AppealStatus::Pending, $appeal->status);
        Event::assertDispatched(AppealFiled::class);

        // Nothing else changed.
        $this->assertTrue($this->post->fresh()->isRemoved());
    }

    public function test_the_explanation_is_required_and_short(): void
    {
        $this->appeal(statement: '')->assertUnprocessable()->assertJsonValidationErrors('statement');
        $this->appeal(statement: '   ')->assertUnprocessable();
        $this->appeal(statement: str_repeat('a', 501))->assertUnprocessable();
        $this->appeal(statement: str_repeat('a', 500))->assertCreated();
    }

    public function test_a_verdict_is_appealed_once(): void
    {
        $this->appeal()->assertCreated();
        $this->appeal()->assertForbidden();

        $this->assertSame(1, Appeal::count());
    }

    public function test_it_must_be_within_seven_days(): void
    {
        $this->travel(6)->days();
        $this->assertTrue($this->author->can('appeal', $this->verdict->fresh()));

        $this->travel(2)->days();
        $this->appeal()->assertForbidden();
        $this->assertSame(0, Appeal::count());
    }

    public function test_only_the_author_may_and_nobody_else_learns_the_verdict_exists(): void
    {
        // First: after actingAs, nobody is a guest any more in the same test.
        $this->postJson(route('appeals.store', $this->verdict->id), ['statement' => 'x'])->assertUnauthorized();

        $this->appeal(User::factory()->create())->assertNotFound();
        $this->assertSame(0, Appeal::count());
    }

    public function test_an_unverified_author_is_sent_to_verify_first(): void
    {
        $this->author->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->author)->post(route('appeals.store', $this->verdict->id), ['statement' => 'x'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_verdict_that_punished_nobody_has_nothing_to_appeal(): void
    {
        $post = Post::factory()->create(['group_id' => $this->group->id, 'user_id' => $this->author->id]);
        $cleared = $this->decide($post, ModerationVerdictType::NoViolation);

        $this->appeal(verdict: $cleared)->assertForbidden();
    }

    public function test_a_comments_author_appeals_too(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->author->id, 'text' => 'You are a complete idiot']);
        $verdict = $this->decide($comment, ModerationVerdictType::Warning);

        $this->appeal(verdict: $verdict)->assertCreated();
    }

    public function test_being_banned_does_not_stop_an_appeal(): void
    {
        Punishment::create([
            'user_id' => $this->author->id, 'group_id' => $this->group->id, 'kind' => SanctionKind::Ban,
            'step' => 1, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(),
        ]);

        $this->appeal()->assertCreated();
    }

    public function test_an_unknown_verdict_is_not_found(): void
    {
        $this->actingAs($this->author)->postJson(route('appeals.store', (string) \Illuminate\Support\Str::uuid()), ['statement' => 'x'])->assertNotFound();
    }

    public function test_the_authors_page_says_whether_it_can_still_be_appealed_and_shows_the_appeal(): void
    {
        $url = route('posts.show', [$this->group->slug, $this->post->slug]);

        $this->actingAs($this->author)->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation.verdict.id', $this->verdict->id)
            ->where('post.moderation.verdict.can_appeal', true)
            ->has('post.moderation.verdict.appeal_until')
            ->where('post.moderation.verdict.appeal', null));

        $this->appeal()->assertCreated();

        $this->actingAs($this->author)->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation.verdict.can_appeal', false)
            ->where('post.moderation.verdict.appeal.status', 'pending')
            ->where('post.moderation.verdict.appeal.statement', 'It was a quote from a film.'));
    }

    public function test_after_the_window_it_can_no_longer_be_appealed_and_nobody_else_sees_any_of_it(): void
    {
        $url = route('posts.show', [$this->group->slug, $this->post->slug]);
        $this->travel(8)->days();

        $this->actingAs($this->author)->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation.verdict.can_appeal', false)
            ->where('post.moderation.verdict.appeal', null));

        $this->actingAs(User::factory()->create())->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation', null));
    }
}
