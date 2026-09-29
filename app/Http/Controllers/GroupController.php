<?php

namespace App\Http\Controllers;

use App\Enums\PostSort;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Models\Group;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Services\GroupService;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
        protected PostService $postService,
        protected LanguageRepositoryInterface $languageRepository
    ) {}

    public function create(): Response
    {
        $this->authorize('create', Group::class);

        return Inertia::render('Groups/Create', [
            'languages' => $this->languageRepository->speakingLanguages(),
            'limits' => [
                'slug' => GroupService::SLUG_MAX_LENGTH,
                'rules' => StoreGroupRequest::MAX_RULES,
                'rule_text' => StoreGroupRequest::RULE_TEXT_MAX_LENGTH,
                'rule_example' => StoreGroupRequest::RULE_EXAMPLE_MAX_LENGTH,
            ],
        ]);
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

        return Inertia::render('Groups/Show', [
            'group' => new GroupResource($group),
            'is_member' => $isMember,
            'sort' => $sort->value,
            // null hides the posts of a private group from non-members.
            'posts' => $this->groupService->canViewPosts($group, $isMember)
                ? Inertia::scroll(fn () => PostResource::collection($this->postService->getGroupPosts($group->id, $sort, $viewerId)))
                : null,
        ]);
    }
}
