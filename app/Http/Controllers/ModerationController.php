<?php

namespace App\Http\Controllers;

use App\Enums\ModerationCaseStatus;
use App\Http\Resources\GroupResource;
use App\Http\Resources\ModerationCaseListItemResource;
use App\Http\Resources\ModerationCaseResource;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        protected GroupService $groupService,
        protected GroupRepositoryInterface $groupRepository,
        protected ModerationCaseRepositoryInterface $moderationCaseRepository
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

        $case = $this->moderationCaseRepository->findForModeration($caseId);

        // A case of another group is "not found" here, not "forbidden": a moderator must not be able
        // to tell that a case with this id exists elsewhere. Neither is one that has been closed.
        abort_if($case === null || $case->group_id !== $group->id || $case->status !== ModerationCaseStatus::Open, 404);

        $userId = $request->user()->id;

        return Inertia::render('Groups/ModerationCase', [
            'group' => new GroupResource($group),
            'is_member' => $this->groupService->isMember($group->id, $userId),
            'can_edit' => $request->user()->can('update', $group),
            'role' => $this->groupRepository->moderatorRole($group->id, $userId)?->key(),
            'moderation_case' => new ModerationCaseResource($case),
        ]);
    }
}
