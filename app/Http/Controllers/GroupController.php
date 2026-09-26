<?php

namespace App\Http\Controllers;

use App\Enums\PostSort;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Services\GroupService;
use App\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
        protected PostService $postService
    ) {}

    public function show(Request $request, string $id): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when the group does not exist.
        $group = $this->groupService->getGroup($id);

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
