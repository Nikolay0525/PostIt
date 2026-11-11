<?php

namespace App\Http\Controllers;

use App\Enums\PostSort;
use App\Enums\SanctionKind;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\RestrictionResource;
use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\SanctionRepositoryInterface;
use App\Services\GroupService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
        protected PostService $postService,
        protected GroupRepositoryInterface $groupRepository,
        protected LanguageRepositoryInterface $languageRepository,
        protected SanctionRepositoryInterface $sanctionRepository
    ) {}

    public function create(Request $request): Response
    {
        $this->authorize('create', Group::class);

        return Inertia::render('Groups/Create', [
            'languages' => $this->languageRepository->speakingLanguages(),
            // Pre-select a language the creator speaks rather than whatever sorts first.
            'default_language_code' => $request->user()->speakingLanguages()->value('code')
                ?? config('app.default_speaking_language_code', 'uk'),
            'limits' => $this->limits(),
        ]);
    }

    // The group's current rules, for the report form: it asks which of them was broken. Read-only
    // and as open as the rules on the group's page.
    public function rules(string $id): JsonResponse
    {
        $group = $this->groupRepository->find($id);

        abort_if($group === null, 404);

        return response()->json(['rules' => $group->currentRuleVersion?->rules ?? []]);
    }

    public function edit(string $groupSlug): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when the group does not exist.
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $this->authorize('update', $group);

        return Inertia::render('Groups/Edit', [
            'group' => new GroupResource($group),
            'languages' => $this->languageRepository->speakingLanguages(),
            'limits' => $this->limits(),
        ]);
    }

    public function update(UpdateGroupRequest $request, string $groupSlug): RedirectResponse
    {
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $this->authorize('update', $group);

        $this->groupService->updateGroup(
            $group,
            $request->validated('name'),
            $request->validated('description'),
            $request->validated('language_code'),
            $request->validated('rules') ?? [],
        );

        return redirect()->route('groups.show', $group->slug);
    }

    // A regular Inertia form submission, like PostController::store(): the next step is a full
    // navigation to the new group, so there's no list on this page to preserve.
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', Group::class);

        $group = $this->groupService->createGroup(
            $request->user()->id,
            $request->validated('name'),
            $request->validated('slug'),
            $request->validated('description'),
            $request->validated('rules') ?? [],
            $request->validated('language_code'),
            $request->boolean('is_private'),
        );

        return redirect()->route('groups.show', $group->slug);
    }

    public function show(Request $request, string $groupSlug): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when the group does not exist.
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $viewerId = $request->user()?->id;
        $isMember = $this->groupService->isMember($group->id, $viewerId);
        $sort = PostSort::tryFrom((string) $request->query('sort')) ?? PostSort::Newest;
        $restriction = $viewerId === null ? null : $this->sanctionRepository->runningIn($viewerId, $group->id);

        return Inertia::render('Groups/Show', [
            'group' => new GroupResource($group),
            'is_member' => $isMember,
            // Only the Owner is offered the edit link; the route itself is guarded by the policy too.
            'can_edit' => (bool) $request->user()?->can('update', $group),
            // The viewer's own role, for them alone: "owner", "moderator" or null.
            'role' => $viewerId === null ? null : $this->groupRepository->moderatorRole($group->id, $viewerId)?->key(),
            // The mute or ban the viewer is under in this group, for them alone; otherwise null.
            'restriction' => $restriction === null ? null : new RestrictionResource($restriction),
            'sort' => $sort->value,
            // null hides the posts of a private group from non-members, and of any group from the banned.
            'posts' => $this->groupService->canViewPosts($group, $isMember, $restriction?->kind === SanctionKind::Ban)
                ? Inertia::scroll(fn () => PostResource::collection($this->postService->getGroupPosts($group->id, $sort, $viewerId)))
                : null,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function limits(): array
    {
        return [
            'slug' => GroupService::SLUG_MAX_LENGTH,
            'rules' => StoreGroupRequest::MAX_RULES,
            'rule_text' => StoreGroupRequest::RULE_TEXT_MAX_LENGTH,
            'rule_example' => StoreGroupRequest::RULE_EXAMPLE_MAX_LENGTH,
        ];
    }
}
