<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Services\CommentService;
use App\Services\PostService;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected CommentService $commentService
    ) {}

    public function show(string $id): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when the post does not exist.
        $post = $this->postService->getPost($id);

        return Inertia::render('Posts/Show', [
            'post' => new PostResource($post),
            // Merged page by page by the <InfiniteScroll> component on the client.
            'comments' => Inertia::scroll(
                fn () => CommentResource::collection($this->commentService->getPostThreads($id))
            ),
        ]);
    }
}
