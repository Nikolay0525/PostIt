<?php

namespace Tests\Feature\Services;

use App\Enums\ReportTargetType;
use App\Events\ModerationCaseOpened;
use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\ModerationCaseService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Reports coming in, and a case opening once enough of them have.
 */
class ModerationCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    /** Someone with a good name in the group, so that their report counts. */
    private function reputableIn(Group $group): User
    {
        $user = User::factory()->create();
        DB::table('user_group_karma')->insert(['user_id' => $user->id, 'group_id' => $group->id, 'karma' => 1]);

        return $user;
    }

    private function report(User $reporter, Post|Comment $target): void
    {
        $this->actingAs($reporter)->postJson('/reports', [
            'target_type' => $target instanceof Post ? 'post' : 'comment',
            'target_id' => $target->id,
            'text' => 'Spam',
        ])->assertCreated();
    }

    private function setActivity(Group $group, int $voters): void
    {
        GroupActivity::create([
            'group_id' => $group->id,
            'voters_count' => $voters,
            'votes_count' => $voters * 3,
            'computed_at' => now(),
        ]);
    }

    public function test_a_group_not_counted_yet_needs_the_minimum_number_of_reports(): void
    {
        $group = Group::factory()->create();

        $this->assertSame(ModerationCaseService::MIN_REPORTS, app(ModerationCaseService::class)->reportsNeeded($group->id));
    }

    public function test_a_livelier_group_needs_a_share_of_its_voters_but_never_less_than_the_minimum(): void
    {
        $quiet = Group::factory()->create();
        $this->setActivity($quiet, 20); // 5% is 1
        $lively = Group::factory()->create();
        $this->setActivity($lively, 200); // 5% is 10

        $service = app(ModerationCaseService::class);
        $this->assertSame(3, $service->reportsNeeded($quiet->id));
        $this->assertSame(10, $service->reportsNeeded($lively->id));
    }

    public function test_enough_reports_open_a_case_and_it_takes_the_reports(): void
    {
        Event::fake([ModerationCaseOpened::class]);
        $post = Post::factory()->create();

        foreach (range(1, 2) as $_) {
            $this->report($this->reputableIn($post->group), $post);
        }
        $this->assertSame(0, ModerationCase::count());

        $this->report($this->reputableIn($post->group), $post);

        $case = ModerationCase::firstOrFail();
        $this->assertSame($post->group_id, $case->group_id);
        $this->assertSame(ReportTargetType::Post, $case->target_type);
        $this->assertSame($post->id, $case->target_id);
        $this->assertSame(3, $case->reports()->count());
        $this->assertNotNull($case->open_key);
        Event::assertDispatched(ModerationCaseOpened::class, fn ($event) => $event->case->is($case));
    }

    public function test_reports_of_comments_open_a_case_on_the_comment(): void
    {
        $comment = Comment::factory()->create();
        $group = $comment->post->group;

        foreach (range(1, 3) as $_) {
            $this->report($this->reputableIn($group), $comment);
        }

        $this->assertSame(ReportTargetType::Comment, ModerationCase::firstOrFail()->target_type);
    }

    public function test_reports_from_people_with_no_standing_in_the_group_do_not_count(): void
    {
        $post = Post::factory()->create();

        foreach (range(1, 5) as $_) {
            $this->report(User::factory()->create(), $post); // no sub-karma in the group
        }

        $this->assertSame(5, Report::count());
        $this->assertSame(0, ModerationCase::count());
    }

    public function test_negative_standing_does_not_count_either(): void
    {
        $post = Post::factory()->create();

        foreach (range(1, 3) as $_) {
            $user = User::factory()->create();
            DB::table('user_group_karma')->insert(['user_id' => $user->id, 'group_id' => $post->group_id, 'karma' => -4]);
            $this->report($user, $post);
        }

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_reputation_in_another_group_does_not_count(): void
    {
        $post = Post::factory()->create();
        $elsewhere = Group::factory()->create();

        foreach (range(1, 3) as $_) {
            $this->report($this->reputableIn($elsewhere), $post);
        }

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_a_livelier_group_needs_more_reports(): void
    {
        $post = Post::factory()->create();
        $this->setActivity($post->group, 200); // 10 reports

        foreach (range(1, 9) as $_) {
            $this->report($this->reputableIn($post->group), $post);
        }
        $this->assertSame(0, ModerationCase::count());

        $this->report($this->reputableIn($post->group), $post);
        $this->assertSame(1, ModerationCase::count());
    }

    public function test_more_reports_on_content_with_an_open_case_do_not_open_another(): void
    {
        $post = Post::factory()->create();

        foreach (range(1, 6) as $_) {
            $this->report($this->reputableIn($post->group), $post);
        }

        $this->assertSame(1, ModerationCase::count());
        // The first three made the case; the later ones are not part of it.
        $this->assertSame(3, ModerationCase::firstOrFail()->reports()->count());
    }

    public function test_reports_on_two_posts_are_counted_apart(): void
    {
        $group = Group::factory()->create();
        $first = Post::factory()->create(['group_id' => $group->id]);
        $second = Post::factory()->create(['group_id' => $group->id]);

        foreach (range(1, 2) as $_) {
            $this->report($this->reputableIn($group), $first);
            $this->report($this->reputableIn($group), $second);
        }

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_content_can_have_a_new_case_after_the_old_one_is_closed(): void
    {
        $post = Post::factory()->create();

        foreach (range(1, 3) as $_) {
            $this->report($this->reputableIn($post->group), $post);
        }
        ModerationCase::firstOrFail()->update(['status' => \App\Enums\ModerationCaseStatus::Closed, 'open_key' => null]);

        foreach (range(1, 3) as $_) {
            $this->report($this->reputableIn($post->group), $post);
        }

        $this->assertSame(2, ModerationCase::count());
    }
}
