<?php

namespace Tests\Feature\Http;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
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
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The group's moderation log: for its members only; the verdicts that punished, as short cards, each in
 * full on its own page with the conversation as it was then, the rule and the reason; each shown to
 * everyone half an hour after, to its author at once.
 */
class ModerationLogTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $member;

    private User $author;

    private string $versionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create();
        $this->versionId = app(GroupRepositoryInterface::class)->addRuleVersion($this->group->id, [
            ['text' => 'Be respectful', 'example' => null, 'kind' => 'mute'],
        ])->id;
        $this->member = User::factory()->create();
        $this->author = User::factory()->create();
        foreach ([$this->member, $this->author] as $user) {
            app(GroupRepositoryInterface::class)->subscribe($this->group->id, $user->id);
        }
    }

    private function decide(Post|Comment $content, ModerationVerdictType $type = ModerationVerdictType::Warning): ModerationVerdict
    {
        $case = app(ModerationCaseRepositoryInterface::class)->open(
            $this->group->id,
            $content instanceof Post ? ReportTargetType::Post : ReportTargetType::Comment,
            $content->id,
            $this->versionId,
        );

        return app(ModerationVerdictService::class)->decide(
            $case,
            User::factory()->create()->id,
            $type,
            $type->punishes() ? 0 : null,
            $type->punishes() ? [['start' => 0, 'end' => 14, 'quote' => 'complete idiot']] : null,
            $type->punishes() ? 'An insult.' : null,
        );
    }

    private function postBy(User $user): Post
    {
        return Post::factory()->hidden()->create(['group_id' => $this->group->id, 'user_id' => $user->id, 'title' => 'A title', 'article' => 'complete idiot, you are']);
    }

    private function log(?User $viewer = null)
    {
        return $this->actingAs($viewer ?? $this->member)->get(route('groups.moderation_log', $this->group->slug));
    }

    private function entry(ModerationVerdict $verdict, ?User $viewer = null)
    {
        return $this->actingAs($viewer ?? $this->member)->get(route('groups.moderation_log.entry', [$this->group->slug, $verdict->id]));
    }

    public function test_it_is_for_the_groups_members_alone(): void
    {
        $this->get(route('groups.moderation_log', $this->group->slug))->assertRedirect(route('login'));
        $this->log(User::factory()->create())->assertForbidden();

        $this->log()->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Groups/ModerationLog'));
    }

    public function test_a_member_banned_from_the_group_still_sees_it(): void
    {
        Punishment::create([
            'user_id' => $this->member->id, 'group_id' => $this->group->id, 'kind' => SanctionKind::Ban,
            'step' => 1, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(),
        ]);

        $this->log()->assertOk();
    }

    public function test_the_list_shows_short_cards_but_nobody_who(): void
    {
        $verdict = $this->decide($this->postBy($this->author));
        $this->travel(31)->minutes();

        $response = $this->log()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $verdict->id)
            ->where('entries.data.0.type', 'warning')
            ->where('entries.data.0.rule.text', 'Be respectful')
            ->where('entries.data.0.content', 'post')
            ->where('entries.data.0.title', 'A title')
            ->where('entries.data.0.excerpt', 'complete idiot, you are')
            ->where('entries.data.0.is_own', false)
            ->missing('entries.data.0.reason')
            ->missing('entries.data.0.context'));

        $json = json_encode($response->viewData('page')['props']['entries']);
        $this->assertStringNotContainsString($this->author->id, $json);
        $this->assertStringNotContainsString($this->author->username, $json);
    }

    public function test_an_entry_shows_what_was_decided_and_why_but_nobody_who(): void
    {
        $verdict = $this->decide($this->postBy($this->author));
        $this->travel(31)->minutes();

        $response = $this->entry($verdict)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Groups/ModerationLogEntry')
            ->where('entry.id', $verdict->id)
            ->where('entry.type', 'warning')
            ->where('entry.rule.text', 'Be respectful')
            ->where('entry.rule.kind', 'mute')
            ->where('entry.marks.0.quote', 'complete idiot')
            ->where('entry.reason', 'An insult.')
            ->where('entry.context.post.title', 'A title')
            ->where('entry.context.post.label.kind', 'author')
            ->where('entry.is_own', false)
            ->where('entry.author_view', null));

        $json = json_encode($response->viewData('page')['props']['entry']);
        $this->assertStringNotContainsString($this->author->id, $json);
        $this->assertStringNotContainsString($this->author->username, $json);
        $this->assertStringNotContainsString('decided_by', $json);
    }

    public function test_an_entry_shows_to_others_half_an_hour_after_and_to_its_author_at_once(): void
    {
        $verdict = $this->decide($this->postBy($this->author));

        $this->log()->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 0));
        $this->entry($verdict)->assertNotFound();

        $this->log($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.is_own', true));
        $this->entry($verdict, $this->author)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entry.is_own', true)
            ->where('entry.author_view.can_appeal', true));

        $this->travel(31)->minutes();
        $this->log()->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 1));
        $this->entry($verdict)->assertOk();
    }

    public function test_an_entry_is_for_members_but_its_author_sees_it_after_leaving(): void
    {
        $verdict = $this->decide($this->postBy($this->author));
        $this->travel(31)->minutes();

        $this->get(route('groups.moderation_log.entry', [$this->group->slug, $verdict->id]))->assertRedirect(route('login'));
        $this->entry($verdict, User::factory()->create())->assertForbidden();

        app(GroupRepositoryInterface::class)->unsubscribe($this->group->id, $this->author->id);
        $this->entry($verdict, $this->author)->assertOk();
    }

    public function test_an_entry_is_not_found_under_another_group_nor_if_it_did_not_punish(): void
    {
        $verdict = $this->decide($this->postBy($this->author));
        $cleared = $this->decide($this->postBy($this->author), ModerationVerdictType::NoViolation);
        $this->travel(31)->minutes();

        $other = Group::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($other->id, $this->member->id);

        $this->actingAs($this->member)->get(route('groups.moderation_log.entry', [$other->slug, $verdict->id]))->assertNotFound();
        $this->entry($cleared)->assertNotFound();
    }

    public function test_only_verdicts_that_punished_and_only_this_groups(): void
    {
        $this->decide($this->postBy($this->author), ModerationVerdictType::NoViolation);
        $this->decide($this->postBy($this->author), ModerationVerdictType::Remove);

        $other = Group::factory()->create();
        $otherPost = Post::factory()->hidden()->create(['group_id' => $other->id, 'article' => 'complete idiot']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($other->id, ReportTargetType::Post, $otherPost->id,
            app(GroupRepositoryInterface::class)->addRuleVersion($other->id, [['text' => 'Be kind', 'example' => null]])->id);
        app(ModerationVerdictService::class)->decide($case, User::factory()->create()->id, ModerationVerdictType::Remove, 0, [['start' => 0, 'end' => 14, 'quote' => 'complete idiot']], 'Rude.');

        $this->travel(31)->minutes();

        $this->log()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.type', 'remove'));
    }

    public function test_the_conversation_is_kept_as_it_was_when_the_verdict_was_given(): void
    {
        $post = Post::factory()->create(['group_id' => $this->group->id]);
        $comment = Comment::factory()->hidden()->create(['post_id' => $post->id, 'user_id' => $this->author->id, 'text' => 'complete idiot']);
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $comment->id, 'text' => 'said before the verdict', 'created_at' => now()->subMinute()]);
        $verdict = $this->decide($comment);

        // Said after: not in what the moderator saw.
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $comment->id, 'text' => 'said after the verdict']);
        $this->travel(31)->minutes();

        $this->log()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.content', 'comment')
            ->where('entries.data.0.excerpt', 'complete idiot'));
        $this->entry($verdict)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entry.context.comment.text', 'complete idiot')
            ->has('entry.context.replies', 1)
            ->where('entry.context.replies.0.text', 'said before the verdict'));
    }

    public function test_an_unknown_group_is_not_found(): void
    {
        $this->log()->assertOk();
        $this->actingAs($this->member)->get(route('groups.moderation_log', 'no-such-group'))->assertNotFound();
    }
}
