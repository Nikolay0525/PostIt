<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\CommentService;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected CommentService $commentService,
        protected GroupRepositoryInterface $groupRepository
    ) {}

    public function show(Request $request, string $id): Response
    {
        $viewerId = $request->user()?->id;

        // Throws ModelNotFoundException (rendered as 404) when the post does not exist.
        $post = $this->postService->getPost($id, $viewerId);

        return Inertia::render('Posts/Show', [
            'post' => new PostResource($post),
            // Merged page by page by the <InfiniteScroll> component on the client.
            'comments' => Inertia::scroll(
                fn () => CommentResource::collection($this->commentService->getPostThreads($id, $viewerId))
            ),
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

        return redirect()->route('posts.show', $post->id);
    }
}
