<?php

namespace Tests\Feature\Services;

use App\Enums\GroupModeratorRole;
use App\Enums\ReportTargetType;
use App\Http\Resources\CaseContextResource;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationContextService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * A case's context: the post, the comments the reported one answers up the thread, and the replies to
 * it — not the rest of the thread — with labels instead of names.
 */
class ModerationContextTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Post $post;

    private User $author;

    /** @var array<string, User> */
    private array $people;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create();
        $this->author = User::factory()->create(['username' => 'the_reported']);
        $this->people = [
            'poster' => User::factory()->create(['username' => 'the_poster']),
            'b' => User::factory()->create(['username' => 'person_b']),
            'c' => User::factory()->create(['username' => 'person_c']),
            'd' => User::factory()->create(['username' => 'person_d']),
        ];
        $this->post = Post::factory()->create(['group_id' => $this->group->id, 'user_id' => $this->people['poster']->id, 'title' => 'Sunday food']);
    }

    private function comment(User $by, ?Comment $parent, string $text, int $minutesAgo): Comment
    {
        return Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $parent?->id,
            'user_id' => $by->id,
            'text' => $text,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function caseOn(Post|Comment $content): ModerationCase
    {
        return app(ModerationCaseRepositoryInterface::class)->open(
            $this->group->id,
            $content instanceof Post ? ReportTargetType::Post : ReportTargetType::Comment,
            $content->id,
        );
    }

    private function context(ModerationCase $case): array
    {
        return json_decode((new CaseContextResource(app(ModerationContextService::class)->forCase($case)))
            ->toResponse(Request::create('/'))->getContent(), true);
    }

    /**
     *   c1 (c) ── c2 (b) ── reported (author) ── r1 (b) ── deeper (c)
     *          │          │                    ├ r2 (author)
     *          │          │                    └ r3 (d)
     *          │          └ sibling (d)
     *   other thread (d)
     */
    private function thread(): Comment
    {
        $c1 = $this->comment($this->people['c'], null, 'Roast or nothing', 60);
        $c2 = $this->comment($this->people['b'], $c1, 'You roast every week?', 50);
        $reported = $this->comment($this->author, $c2, 'Only an idiot asks that, lol', 40);
        $r1 = $this->comment($this->people['b'], $reported, 'haha fair', 30);
        $this->comment($this->author, $reported, 'joking, mate', 25);
        $this->comment($this->people['d'], $reported, 'that was rude', 20);
        $this->comment($this->people['c'], $r1, 'too deep to matter', 10);
        $this->comment($this->people['d'], $c2, 'a sibling, not part of it', 45);
        $this->comment($this->people['d'], null, 'another thread altogether', 5);

        return $reported;
    }

    public function test_a_comment_comes_with_its_post_the_comments_it_answers_and_the_replies_to_it_and_nothing_else(): void
    {
        $context = $this->context($this->caseOn($this->thread()));

        $this->assertSame('Sunday food', $context['post']['title']);
        $this->assertSame(['Roast or nothing', 'You roast every week?'], array_column($context['ancestors'], 'text'));
        $this->assertSame('Only an idiot asks that, lol', $context['comment']['text']);
        $this->assertSame(['haha fair', 'joking, mate', 'that was rude'], array_column($context['replies'], 'text'));
    }

    public function test_everyone_is_labelled_in_reading_order_and_the_reported_person_is_the_author_wherever_they_speak(): void
    {
        $context = $this->context($this->caseOn($this->thread()));

        // The post's author comes first, then the thread down, then the replies.
        $this->assertSame(['kind' => 'participant', 'number' => 1], $context['post']['label']);
        $this->assertSame([2, 3], array_column(array_column($context['ancestors'], 'label'), 'number'));
        $this->assertSame(['kind' => 'author', 'number' => null], $context['comment']['label']);
        $this->assertSame(
            [['kind' => 'participant', 'number' => 3], ['kind' => 'author', 'number' => null], ['kind' => 'participant', 'number' => 4]],
            array_column($context['replies'], 'label')
        );
    }

    public function test_no_name_and_no_id_of_anyone_in_it_leaves_the_server(): void
    {
        $json = json_encode($this->context($this->caseOn($this->thread())));

        foreach ([$this->author, ...array_values($this->people)] as $person) {
            $this->assertStringNotContainsString($person->id, $json);
            $this->assertStringNotContainsString($person->username, $json);
        }
        $this->assertStringNotContainsString('user_id', $json);
    }

    public function test_the_post_author_is_the_author_if_they_are_the_one_reported(): void
    {
        $reported = $this->comment($this->people['poster'], null, 'my own post, my rules', 5);

        $context = $this->context($this->caseOn($reported));

        $this->assertSame('author', $context['post']['label']['kind']);
    }

    public function test_a_deep_thread_keeps_the_nearest_comments_and_a_busy_one_the_first_replies(): void
    {
        $parent = null;
        foreach (range(1, 25) as $level) {
            $parent = $this->comment($this->people['b'], $parent, "level {$level}", 200 - $level);
        }
        foreach (range(1, 25) as $n) {
            $this->comment($this->people['c'], $parent, "reply {$n}", 100 - $n);
        }

        $context = $this->context($this->caseOn($parent));

        $this->assertCount(ModerationContextService::MAX_ANCESTORS, $context['ancestors']);
        $this->assertSame('level 5', $context['ancestors'][0]['text']);
        $this->assertSame('level 24', end($context['ancestors'])['text']);

        $this->assertCount(ModerationContextService::MAX_REPLIES, $context['replies']);
        $this->assertSame('reply 1', $context['replies'][0]['text']);
    }

    public function test_a_comment_its_author_deleted_keeps_its_place_but_not_its_text_while_a_removed_one_is_shown(): void
    {
        $c1 = $this->comment($this->people['c'], null, 'I deleted this myself', 60);
        $c1->update(['is_deleted' => true]);
        $c2 = $this->comment($this->people['b'], $c1, 'removed by a verdict', 50);
        $c2->update(['removed_at' => now()]);
        $reported = $this->comment($this->author, $c2, 'the reported one', 40);

        $context = $this->context($this->caseOn($reported));

        $this->assertSame('', $context['ancestors'][0]['text']);
        $this->assertTrue($context['ancestors'][0]['is_deleted']);
        $this->assertSame('removed by a verdict', $context['ancestors'][1]['text']);
        $this->assertTrue($context['ancestors'][1]['is_removed']);
    }

    public function test_a_case_on_a_post_has_the_post_alone(): void
    {
        $this->thread();

        $context = $this->context($this->caseOn($this->post));

        $this->assertSame('Sunday food', $context['post']['title']);
        $this->assertSame('author', $context['post']['label']['kind']);
        $this->assertSame([], $context['ancestors']);
        $this->assertNull($context['comment']);
        $this->assertSame([], $context['replies']);
    }

    public function test_the_case_page_sends_it_to_the_moderator(): void
    {
        $moderator = User::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator($this->group->id, $moderator->id, GroupModeratorRole::Moderator);
        $case = $this->caseOn($this->thread());

        $this->actingAs($moderator)->get(route('groups.moderation.case', [$this->group->slug, $case->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('context.comment.text', 'Only an idiot asks that, lol')
                ->where('context.comment.label.kind', 'author')
                ->has('context.ancestors', 2)
                ->has('context.replies', 3));
    }
}
