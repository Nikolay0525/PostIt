<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationCaseStatus;
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
 * Reports coming in, and a case opening once enough of them have. Most tests make the group lively
 * (200 people voting: 10 reports needed) so that they do not depend on the minimum.
 */
class ModerationCaseTest extends TestCase
{
    use RefreshDatabase;

    private const NEEDED = 10;

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

    private function report(User $reporter, Post|Comment $target)
    {
        return $this->actingAs($reporter)->postJson('/reports', [
            'target_type' => $target instanceof Post ? 'post' : 'comment',
            'target_id' => $target->id,
            'text' => 'Spam',
        ]);
    }

    /** $count reports on the target, each from someone who counts. */
    private function fileReports(Post|Comment $target, Group $group, int $count): void
    {
        foreach (range(1, $count) as $_) {
            $this->report($this->reputableIn($group), $target)->assertCreated();
        }
    }

    private function livelyGroup(): Group
    {
        $group = Group::factory()->create();
        $this->setActivity($group, 200);

        return $group;
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
        $this->setActivity($quiet, 20); // 5% is 1, below the minimum
        $lively = $this->livelyGroup(); // 5% is 10

        $service = app(ModerationCaseService::class);
        $this->assertSame(ModerationCaseService::MIN_REPORTS, $service->reportsNeeded($quiet->id));
        $this->assertSame(self::NEEDED, $service->reportsNeeded($lively->id));
    }

    public function test_enough_reports_open_a_case_and_it_takes_the_reports(): void
    {
        Event::fake([ModerationCaseOpened::class]);
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);

        $this->fileReports($post, $group, self::NEEDED - 1);
        $this->assertSame(0, ModerationCase::count());

        $this->fileReports($post, $group, 1);

        $case = ModerationCase::firstOrFail();
        $this->assertSame($group->id, $case->group_id);
        $this->assertSame(ReportTargetType::Post, $case->target_type);
        $this->assertSame($post->id, $case->target_id);
        $this->assertSame(self::NEEDED, $case->reports()->count());
        $this->assertNotNull($case->open_key);
        Event::assertDispatched(ModerationCaseOpened::class, fn ($event) => $event->case->is($case));
    }

    public function test_reports_of_comments_open_a_case_on_the_comment(): void
    {
        $group = $this->livelyGroup();
        $comment = Comment::factory()->create(['post_id' => Post::factory()->create(['group_id' => $group->id])->id]);

        $this->fileReports($comment, $group, self::NEEDED);

        $this->assertSame(ReportTargetType::Comment, ModerationCase::firstOrFail()->target_type);
    }

    public function test_reports_from_people_with_no_standing_in_the_group_do_not_count(): void
    {
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);

        foreach (range(1, self::NEEDED + 2) as $_) {
            $this->report(User::factory()->create(), $post)->assertCreated(); // no sub-karma in the group
        }

        $this->assertSame(self::NEEDED + 2, Report::count());
        $this->assertSame(0, ModerationCase::count());
    }

    public function test_negative_standing_does_not_count_either(): void
    {
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);

        foreach (range(1, self::NEEDED) as $_) {
            $user = User::factory()->create();
            DB::table('user_group_karma')->insert(['user_id' => $user->id, 'group_id' => $group->id, 'karma' => -4]);
            $this->report($user, $post)->assertCreated();
        }

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_reputation_in_another_group_does_not_count(): void
    {
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);
        $elsewhere = Group::factory()->create();

        foreach (range(1, self::NEEDED) as $_) {
            $this->report($this->reputableIn($elsewhere), $post)->assertCreated();
        }

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_reports_on_two_posts_are_counted_apart(): void
    {
        $group = $this->livelyGroup();
        $first = Post::factory()->create(['group_id' => $group->id]);
        $second = Post::factory()->create(['group_id' => $group->id]);

        $this->fileReports($first, $group, self::NEEDED - 1);
        $this->fileReports($second, $group, self::NEEDED - 1);

        $this->assertSame(0, ModerationCase::count());
    }

    public function test_after_a_case_opens_the_content_takes_no_more_reports_and_gets_no_second_case(): void
    {
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);
        $this->fileReports($post, $group, self::NEEDED);

        // The page it was reported on may still be open for someone: the server refuses it all the same.
        $this->report($this->reputableIn($group), $post)->assertForbidden();

        $this->assertSame(1, ModerationCase::count());
        $this->assertSame(self::NEEDED, Report::count());
    }

    public function test_content_can_have_a_new_case_after_the_old_one_is_closed(): void
    {
        $group = $this->livelyGroup();
        $post = Post::factory()->create(['group_id' => $group->id]);
        $this->fileReports($post, $group, self::NEEDED);

        // What a moderator's decision will do: close the case and, if the content stays, show it again.
        ModerationCase::firstOrFail()->update(['status' => ModerationCaseStatus::Closed, 'open_key' => null]);
        $post->update(['hidden_at' => null]);

        $this->fileReports($post, $group, self::NEEDED);

        $this->assertSame(2, ModerationCase::count());
    }
}
