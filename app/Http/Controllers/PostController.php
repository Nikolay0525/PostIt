<?php

namespace App\Http\Controllers;

use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Http\Requests\MarkPostsSeenRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\RestrictionResource;
use App\Models\Post;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\SanctionRepositoryInterface;
use App\Services\CommentService;
use App\Services\GroupService;
use App\Services\PostService;
use App\Services\SidePanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected CommentService $commentService,
        protected GroupRepositoryInterface $groupRepository,
        protected PostRepositoryInterface $postRepository,
        protected GroupService $groupService,
        protected SanctionRepositoryInterface $sanctionRepository,
        protected ModerationCaseRepositoryInterface $moderationCaseRepository
    ) {}

    public function show(Request $request, string $groupSlug, string $postSlug): Response|RedirectResponse
    {
        // Throws ModelNotFoundException (rendered as 404) when the group does not exist.
        $group = $this->groupService->getGroupBySlug($groupSlug);

        $viewerId = $request->user()?->id;
        $restriction = $viewerId === null ? null : $this->sanctionRepository->runningIn($viewerId, $group->id);

        // A ban closes the group's content: the group's page tells the banned member so.
        if ($restriction?->kind === SanctionKind::Ban) {
            return redirect()->route('groups.show', $group->slug);
        }

        // Throws ModelNotFoundException (rendered as 404) when the group has no such post.
        $post = $this->postService->getPostBySlug($group->id, $postSlug, $viewerId);

        // A private group's post doesn't exist for outsiders: 404, not 403 (PostPolicy::view()).
        abort_unless(Gate::allows('view', $post), 404);

        // Its own author is told how a post taken down by a verdict was dealt with: the rule, the marks, the reason.
        if ($viewerId !== null && $viewerId === $post->user_id && $post->isRemoved()) {
            $post->setRelation('moderationCase', $this->moderationCaseRepository->findLatestFor(ReportTargetType::Post, $post->id));
        }

        // Seen: off the side panel's counters and "Only new" (members only; a guest has neither).
        if ($viewerId !== null) {
            $this->postService->recordView($post->id, $viewerId);
        }

        return Inertia::render('Posts/Show', [
            'post' => new PostResource($post),
            // The group's card beside the post, as on the group's own page.
            'group' => new GroupResource($group),
            'is_member' => $this->groupService->isMember($group->id, $viewerId),
            'can_edit' => (bool) $request->user()?->can('update', $group),
            // The viewer's own role, for them alone: "owner", "moderator" or null.
            'role' => $viewerId === null ? null : $this->groupRepository->moderatorRole($group->id, $viewerId)?->key(),
            // The mute or ban the viewer is under in this group, for them alone; otherwise null.
            'restriction' => $restriction === null ? null : new RestrictionResource($restriction),
            // Merged page by page by the <InfiniteScroll> component on the client.
            'comments' => Inertia::scroll(function () use ($post, $viewerId) {
                $threads = $this->commentService->getPostThreads($post->id, $viewerId);

                // The viewer's own comments that a verdict took down come with the verdict, for them alone.
                if ($viewerId !== null) {
                    $this->commentService->attachVerdictsForAuthor($threads, $viewerId);
                }

                return CommentResource::collection($threads);
            }),
        ]);
    }

    public function create(string $groupSlug): Response
    {
        $group = $this->groupRepository->findBySlug($groupSlug);

        abort_if($group === null, 404);

        $this->authorize('create', [Post::class, $group]);

        return Inertia::render('Posts/Create', [
            'group' => ['id' => $group->id, 'slug' => $group->slug, 'name' => $group->name],
        ]);
    }

    public function edit(Request $request, string $groupSlug, string $postSlug): Response
    {
        $post = $this->findEditable($request, $groupSlug, $postSlug);

        return Inertia::render('Posts/Edit', [
            'post' => [
                'group' => ['slug' => $post->group->slug, 'name' => $post->group->name],
                'slug' => $post->slug,
                'title' => $post->title,
                'article' => $post->article,
            ],
        ]);
    }

    public function update(UpdatePostRequest $request, string $groupSlug, string $postSlug): RedirectResponse
    {
        $post = $this->findEditable($request, $groupSlug, $postSlug);

        $this->postService->updatePost($post, $request->validated('title'), $request->validated('article'));

        return redirect()->route('posts.show', [$groupSlug, $post->slug]);
    }

    // A post nobody may see is "not found" for them (as in show()); one they see but did not write
    // is "forbidden".
    private function findEditable(Request $request, string $groupSlug, string $postSlug): Post
    {
        $group = $this->groupRepository->findBySlug($groupSlug);

        abort_if($group === null, 404);

        // Throws ModelNotFoundException (rendered as 404) when the group has no such post.
        $post = $this->postService->getPostBySlug($group->id, $postSlug, $request->user()->id);

        abort_unless(Gate::allows('view', $post), 404);
        $this->authorize('update', $post);

        return $post;
    }

    // A regular Inertia form submission, unlike VoteController/CommentController: there is no
    // already-scrolled list on the "create post" page to preserve, and the natural next step is
    // a full navigation to the new post anyway, so a normal redirect response fits here.
    public function store(StorePostRequest $request): RedirectResponse
    {
        $group = $this->groupRepository->find($request->validated('group_id'));

        abort_if($group === null, 404);

        $this->authorize('create', [Post::class, $group]);

        $post = $this->postService->createPost(
            $group->id,
            $request->user()->id,
            $request->validated('title'),
            $request->validated('article')
        );

        return redirect()->route('posts.show', [$group->slug, $post->slug]);
    }

    // A plain JSON endpoint like VoteController: the copy-link button sits in feeds with
    // <InfiniteScroll>, which a page visit would reset. Copying the link itself happens in the
    // browser for everyone; this only counts it, once per member.
    public function share(Request $request, string $id): JsonResponse
    {
        $post = $this->postRepository->find($id);

        abort_if($post === null || $post->is_deleted || $request->user()->cannot('view', $post), 404);

        return response()->json(['shares_count' => $this->postService->share($post->id, $request->user()->id)]);
    }

    // Called with a batch of ids by the browser (utils/seen.js) every few seconds and when leaving
    // a page; plain JSON, no page visit. Posts the user may not see are skipped by the repository.
    // Answers with the side panel as it is now, so its counters drop while the user reads.
    public function markSeen(MarkPostsSeenRequest $request, SidePanelService $sidePanelService): JsonResponse
    {
        $userId = $request->user()->id;

        $this->postService->markSeen($request->validated('ids'), $userId);

        return response()->json(['side_panel' => $sidePanelService->forUser($userId)]);
    }

    // "Read all" (the ✓ on a side panel row). Answers with the panel like markSeen(). A group the
    // user isn't in, or someone they don't follow, notes nothing (see the repository).
    public function markGroupSeen(Request $request, string $id, SidePanelService $sidePanelService): JsonResponse
    {
        $userId = $request->user()->id;

        $this->postService->markGroupSeen($id, $userId);

        return response()->json(['side_panel' => $sidePanelService->forUser($userId)]);
    }

    public function markAuthorSeen(Request $request, string $id, SidePanelService $sidePanelService): JsonResponse
    {
        $userId = $request->user()->id;

        $this->postService->markAuthorSeen($id, $userId);

        return response()->json(['side_panel' => $sidePanelService->forUser($userId)]);
    }

    // Read-only, so it's public like show()/groups.show — not gated behind auth. The private
    // group itself stays open; only its posts don't exist for outsiders — the same rule the
    // group page uses for its list (GroupService::canViewPosts()), same 404 as show().
    public function random(Request $request, string $groupSlug): RedirectResponse
    {
        $group = $this->groupRepository->findBySlug($groupSlug);

        abort_if($group === null, 404);

        $viewerId = $request->user()?->id;

        // A banned member is sent to the group's page, which tells them the content is closed.
        if ($viewerId !== null && $this->sanctionRepository->runningIn($viewerId, $group->id)?->kind === SanctionKind::Ban) {
            return redirect()->route('groups.show', $group->slug);
        }

        $isMember = $this->groupService->isMember($group->id, $viewerId);

        abort_unless($this->groupService->canViewPosts($group, $isMember), 404);

        $postSlug = $this->postService->getRandomPostSlug($group->id);

        abort_if($postSlug === null, 404);

        return redirect()->route('posts.show', [$group->slug, $postSlug]);
    }
}
