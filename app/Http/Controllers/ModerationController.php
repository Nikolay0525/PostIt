<?php

namespace App\Http\Controllers;

use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Http\Requests\StoreVerdictRequest;
use App\Http\Resources\CaseContextResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\ModerationCaseListItemResource;
use App\Http\Resources\ModerationCaseResource;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\PlatformRuleRepositoryInterface;
use App\Services\GroupService;
use App\Services\ModerationContextService;
use App\Services\ModerationVerdictService;
use App\Models\ModerationCase;
use App\Models\Group;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        protected GroupService $groupService,
        protected GroupRepositoryInterface $groupRepository,
        protected ModerationCaseRepositoryInterface $moderationCaseRepository,
        protected ModerationContextService $moderationContextService,
        protected ModerationVerdictService $moderationVerdictService,
        protected PlatformRuleRepositoryInterface $platformRuleRepository
    ) {}

    // What the group's moderators did, newest first. Open to everyone, a guest included; nothing is
    // recorded yet, because there is no decision to record.
    public function log(Request $request, string $groupSlug): Response
    {
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $userId = $request->user()?->id;

        return Inertia::render('Groups/ModerationLog', [
            'group' => new GroupResource($group),
            'is_member' => $this->groupService->isMember($group->id, $userId),
            'can_edit' => (bool) $request->user()?->can('update', $group),
            'role' => $userId === null ? null : $this->groupRepository->moderatorRole($group->id, $userId)?->key(),
        ]);
    }

    // The group's queue: cases nobody has decided yet, the longest waiting first.
    public function index(Request $request, string $groupSlug): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when the group does not exist.
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $this->authorize('moderate', $group);

        $userId = $request->user()->id;

        return Inertia::render('Groups/Moderation', [
            'group' => new GroupResource($group),
            // A moderator is a member of the group they moderate; the card needs the same answer as on the group's page.
            'is_member' => $this->groupService->isMember($group->id, $userId),
            'can_edit' => $request->user()->can('update', $group),
            'role' => $this->groupRepository->moderatorRole($group->id, $userId)?->key(),
            'cases' => Inertia::scroll(fn () => ModerationCaseListItemResource::collection(
                $this->moderationCaseRepository->openInGroup($group->id, self::PER_PAGE)
            )),
        ]);
    }

    // One case of the queue, read in full on a page of its own: a post can be thousands of
    // characters, too long to open for every case at once in the list.
    public function show(Request $request, string $groupSlug, string $caseId): Response
    {
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $this->authorize('moderate', $group);

        $case = $this->openCaseOf($group, $caseId);

        $userId = $request->user()->id;

        return Inertia::render('Groups/ModerationCase', [
            'group' => new GroupResource($group),
            'is_member' => $this->groupService->isMember($group->id, $userId),
            'can_edit' => $request->user()->can('update', $group),
            'role' => $this->groupRepository->moderatorRole($group->id, $userId)?->key(),
            'moderation_case' => new ModerationCaseResource($case),
            // The conversation it stands in, by label, so that it is judged in it; see ModerationContextService.
            'context' => new CaseContextResource($this->moderationContextService->forCase($case)),
            // The rules a verdict can name: the group's as they were when the case opened.
            'rules' => $this->rulesOf($case),
            // The one most of the reports named, to start the form with; the moderator decides.
            'suggested_rule' => $this->suggestedRule($case),
            // The platform's rules in force, for passing the case up under one of them.
            'platform_rules' => $this->platformRules(),
        ]);
    }

    // The moderator's decision on a case. Back to the queue, which then has one case less.
    public function decide(StoreVerdictRequest $request, string $groupSlug, string $caseId): RedirectResponse
    {
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $this->authorize('moderate', $group);

        $case = $this->openCaseOf($group, $caseId);
        $ruleIndex = $request->validated('rule_index');

        if ($request->validated('type') === 'pass_up') {
            return $this->passUp($request, $group, $case, $ruleIndex);
        }

        $type = ModerationVerdictType::fromKey($request->validated('type'));

        if ($type->punishes() && ! isset($case->ruleVersion?->rules[$ruleIndex])) {
            throw ValidationException::withMessages(['rule_index' => __('moderation.verdict.unknown_rule')]);
        }

        try {
            $this->moderationVerdictService->decide(
                $case,
                $request->user()->id,
                $type,
                $ruleIndex,
                $request->validated('marks'),
                $request->validated('reason'),
            );
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['marks' => __('moderation.verdict.invalid')]);
        } catch (DomainException) {
            // Another moderator decided it a moment earlier.
            return redirect()->route('groups.moderation', $group->slug)->with('status', 'case-already-decided');
        }

        return redirect()->route('groups.moderation', $group->slug)->with('status', 'verdict-given');
    }

    // Not a verdict: the case goes to the platform's administrators, with the moderator's hint.
    private function passUp(StoreVerdictRequest $request, Group $group, ModerationCase $case, ?int $ruleIndex): RedirectResponse
    {
        if (! isset($this->platformRuleRepository->current()?->rules[$ruleIndex])) {
            throw ValidationException::withMessages(['rule_index' => __('moderation.verdict.unknown_rule')]);
        }

        try {
            $this->moderationVerdictService->passUp($case, $request->user()->id, $ruleIndex, $request->validated('marks'), $request->validated('reason'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['marks' => __('moderation.verdict.invalid')]);
        } catch (DomainException) {
            return redirect()->route('groups.moderation', $group->slug)->with('status', 'case-already-decided');
        }

        return redirect()->route('groups.moderation', $group->slug)->with('status', 'case-passed-up');
    }

    /**
     * @return list<array{index: int, text: string, kind: string}>
     */
    private function platformRules(): array
    {
        $version = $this->platformRuleRepository->current();

        if ($version === null) {
            return [];
        }

        return collect($version->rules)
            ->map(fn (array $rule, int $index) => ['index' => $index, 'text' => $version->textOfRule($index), 'kind' => $version->kindOfRule($index)->key()])
            ->values()
            ->all();
    }

    // A case of another group is "not found" here, not "forbidden": a moderator must not be able to
    // tell that a case with this id exists elsewhere. Neither is one that has been closed.
    private function openCaseOf(Group $group, string $caseId): ModerationCase
    {
        $case = $this->moderationCaseRepository->findForModeration($caseId);

        abort_if($case === null || $case->group_id !== $group->id || $case->status !== ModerationCaseStatus::Open, 404);

        return $case;
    }

    /**
     * @return list<array{index: int, text: string, kind: string}>
     */
    private function rulesOf(ModerationCase $case): array
    {
        $version = $case->ruleVersion;

        if ($version === null) {
            return [];
        }

        return collect($version->rules)
            ->map(fn (array $rule, int $index) => ['index' => $index, 'text' => $rule['text'], 'kind' => $version->kindOfRule($index)->key()])
            ->values()
            ->all();
    }

    private function suggestedRule(ModerationCase $case): ?int
    {
        $named = $case->reports
            ->where('rule_version_id', $case->rule_version_id)
            ->whereNotNull('rule_index')
            ->countBy('rule_index');

        return $named->isEmpty() ? null : (int) $named->sortDesc()->keys()->first();
    }
}
