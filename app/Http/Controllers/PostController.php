<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\CommentService;
use App\Services\GroupService;
use App\Services\PostService;
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
        protected GroupService $groupService
    ) {}

    public function show(Request $request, string $groupSlug, string $postSlug): Response
    {
        $group = $this->groupRepository->findBySlug($groupSlug);

        abort_if($group === null, 404);

        $viewerId = $request->user()?->id;

        // Throws ModelNotFoundException (rendered as 404) when the group has no such post.
        $post = $this->postService->getPostBySlug($group->id, $postSlug, $viewerId);

        // A private group's post doesn't exist for outsiders: 404, not 403 (PostPolicy::view()).
        abort_unless(Gate::allows('view', $post), 404);

        // Remembered for members only; it powers "only new" in recommendations.
        if ($viewerId !== null) {
            $this->postService->recordView($post->id, $viewerId);
        }

        return Inertia::render('Posts/Show', [
            'post' => new PostResource($post),
            // Merged page by page by the <InfiniteScroll> component on the client.
            'comments' => Inertia::scroll(
                fn () => CommentResource::collection($this->commentService->getPostThreads($post->id, $viewerId))
            ),
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

    // Read-only, so it's public like show()/groups.show — not gated behind auth. The private
    // group itself stays open; only its posts don't exist for outsiders — the same rule the
    // group page uses for its list (GroupService::canViewPosts()), same 404 as show().
    public function random(Request $request, string $groupSlug): RedirectResponse
    {
        $group = $this->groupRepository->findBySlug($groupSlug);

        abort_if($group === null, 404);

        $isMember = $this->groupService->isMember($group->id, $request->user()?->id);

        abort_unless($this->groupService->canViewPosts($group, $isMember), 404);

        $postSlug = $this->postService->getRandomPostSlug($group->id);

        abort_if($postSlug === null, 404);

        return redirect()->route('posts.show', [$group->slug, $postSlug]);
    }
}
