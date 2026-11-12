<?php

namespace Tests\Feature\Http;

use App\Enums\ModerationVerdictType;
use App\Enums\NotificationType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Events\ModerationCaseOpened;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationCase;
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
 * What the author of a post sees of its moderation: the notice that it is hidden while the case is
 * open, and, once it is taken down, their own text with the verdict. Nobody else sees any of it.
 */
class AuthorModerationViewTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $author;

    private Post $post;

    private string $versionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create(['is_private' => false]);
        $this->author = User::factory()->create();
        $this->versionId = app(GroupRepositoryInterface::class)->addRuleVersion($this->group->id, [
            ['text' => 'Be respectful', 'example' => null, 'kind' => 'mute'],
            ['text' => 'No threats', 'example' => null, 'kind' => 'ban'],
        ])->id;
        $this->post = Post::factory()->create([
            'group_id' => $this->group->id,
            'user_id' => $this->author->id,
            'title' => 'My own title',
            'article' => 'You are a complete idiot and wrong',
        ]);
    }

    /** Opens the case the way reports do, so the post is hidden and its author told. */
    private function openCase(?Post $post = null): ModerationCase
    {
        $post ??= $this->post;
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Post, $post->id, $this->versionId);
        ModerationCaseOpened::dispatch($case);

        return $case;
    }

    private function decide(ModerationCase $case, ModerationVerdictType $type = ModerationVerdictType::Remove): void
    {
        app(ModerationVerdictService::class)->decide(
            $case,
            User::factory()->create()->id,
            $type,
            0,
            [['start' => 8, 'end' => 22, 'quote' => 'complete idiot']],
            'An insult, not an argument.'
        );
    }

    private function show(?User $viewer = null)
    {
        $request = $viewer === null ? $this : $this->actingAs($viewer);

        return $request->get(route('posts.show', [$this->group->slug, $this->post->slug]));
    }

    public function test_while_the_case_is_open_the_author_sees_their_text_and_that_it_is_waiting(): void
    {
        $this->openCase();

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.is_hidden', true)
            ->where('post.moderation.state', 'pending')
            ->where('post.title', 'My own title')
            ->where('post.article_text', 'You are a complete idiot and wrong')
            ->where('post.author.username', $this->author->username)
            ->where('post.can_edit', false));
    }

    public function test_nobody_else_sees_it_and_nobody_is_told_where_it_stands(): void
    {
        $this->openCase();

        foreach ([$this->show(), $this->show(User::factory()->create())] as $response) {
            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->where('post.is_hidden', true)
                ->where('post.moderation', null)
                ->where('post.title', null)
                ->where('post.article_text', '')
                ->where('post.author.username', 'Hidden'));
        }
    }

    public function test_once_it_is_taken_down_the_author_sees_the_rule_the_marks_and_the_reason(): void
    {
        $this->decide($this->openCase());

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.is_removed', true)
            ->where('post.moderation.state', 'removed')
            ->where('post.moderation.verdict.type', 'remove')
            ->where('post.moderation.verdict.rule.text', 'Be respectful')
            ->where('post.moderation.verdict.rule.kind', 'mute')
            ->where('post.moderation.verdict.marks.0.quote', 'complete idiot')
            ->where('post.moderation.verdict.reason', 'An insult, not an argument.')
            ->where('post.article_text', 'You are a complete idiot and wrong'));
    }

    public function test_the_verdict_names_no_moderator_and_never_reaches_anyone_else(): void
    {
        $this->decide($this->openCase(), ModerationVerdictType::Warning);

        $json = $this->show($this->author)->viewData('page')['props']['post']['moderation'];
        $this->assertArrayNotHasKey('decided_by', $json['verdict']);
        $this->assertStringNotContainsString('decided_by', json_encode($json));

        $this->show(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation', null)
            ->where('post.article_text', ''));
    }

    public function test_a_warning_tells_the_author_the_kind_of_strike_the_rule_gives(): void
    {
        $this->decide($this->openCase(), ModerationVerdictType::Warning);

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.moderation.verdict.type', 'warning')
            ->where('post.moderation.verdict.rule.kind', 'mute'));
    }

    public function test_a_post_restored_by_no_violation_is_shown_to_everyone_again(): void
    {
        $this->decide($this->openCase(), ModerationVerdictType::NoViolation);

        $this->show(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('post.is_hidden', false)
            ->where('post.moderation', null)
            ->where('post.title', 'My own title'));
    }

    public function test_the_author_is_told_when_the_case_opens_and_the_notice_names_and_opens_their_post(): void
    {
        $this->openCase();

        $notification = $this->author->notifications()->sole();
        $this->assertSame(NotificationType::ContentHidden->value, $notification->type);
        $this->assertSame($this->post->id, $notification->data['post_id']);
        $this->assertSame('post', $notification->data['target']);

        $this->actingAs($this->author)->getJson('/notifications')
            ->assertJsonPath('notifications.0.post_title', 'My own title')
            ->assertJsonPath('notifications.0.has_link', true)
            ->assertJsonPath('notifications.0.details.target', 'post')
            ->assertJsonPath('notifications.0.post_unavailable', false);

        $this->actingAs($this->author)->post("/notifications/{$notification->id}/open")
            ->assertRedirect(route('posts.show', [$this->group->slug, $this->post->slug]));
    }

    public function test_a_hidden_comments_author_is_told_too(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->author->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Comment, $comment->id, $this->versionId);
        ModerationCaseOpened::dispatch($case);

        $notification = $this->author->notifications()->sole();
        $this->assertSame('comment', $notification->data['target']);
        $this->assertSame($comment->id, $notification->data['comment_id']);
    }

    public function test_someone_banned_from_the_group_is_not_told(): void
    {
        Punishment::create([
            'user_id' => $this->author->id,
            'group_id' => $this->group->id,
            'kind' => SanctionKind::Ban,
            'step' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->openCase();

        $this->assertCount(0, $this->author->notifications);
    }

    private function caseOnComment(Comment $comment): ModerationCase
    {
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Comment, $comment->id, $this->versionId);
        ModerationCaseOpened::dispatch($case);

        return $case;
    }

    public function test_a_comments_author_sees_it_with_where_it_stands_inside_the_thread(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->author->id, 'text' => 'You are a complete idiot']);
        $this->caseOnComment($comment);

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.is_hidden', true)
            ->where('comments.data.0.moderation.state', 'pending')
            ->where('comments.data.0.text', 'You are a complete idiot')
            ->where('comments.data.0.author.username', $this->author->username)
            ->where('comments.data.0.can_edit', false));

        // Anyone else gets the placeholder and no word of it.
        $this->show(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.moderation', null)
            ->where('comments.data.0.text', '')
            ->where('comments.data.0.author.username', 'Hidden'));
    }

    public function test_a_guest_gets_a_hidden_comment_as_a_placeholder_too(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->author->id, 'text' => 'You are a complete idiot']);
        $this->caseOnComment($comment);

        $this->show()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.moderation', null)
            ->where('comments.data.0.text', '')
            ->where('comments.data.0.author.username', 'Hidden'));
    }

    public function test_a_removed_comment_comes_to_its_author_with_the_verdict_even_as_a_reply(): void
    {
        $parent = Comment::factory()->create(['post_id' => $this->post->id]);
        $reply = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $parent->id, 'user_id' => $this->author->id, 'text' => 'You are a complete idiot']);
        $this->decide($this->caseOnComment($reply));

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.replies.0.moderation.state', 'removed')
            ->where('comments.data.0.replies.0.moderation.verdict.rule.text', 'Be respectful')
            ->where('comments.data.0.replies.0.moderation.verdict.marks.0.quote', 'complete idiot')
            ->where('comments.data.0.replies.0.moderation.verdict.reason', 'An insult, not an argument.')
            ->where('comments.data.0.replies.0.text', 'You are a complete idiot'));

        $this->show(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.replies.0.moderation', null)
            ->where('comments.data.0.replies.0.text', ''));
    }

    public function test_a_comment_a_user_deleted_themselves_stays_gone_even_for_them(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->author->id, 'is_deleted' => true, 'hidden_at' => now()]);

        $this->show($this->author)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('comments.data.0.moderation', null)
            ->where('comments.data.0.text', ''));
    }
}
