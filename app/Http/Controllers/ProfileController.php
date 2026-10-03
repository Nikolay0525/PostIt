<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserProfileResource;
use App\Services\AchievementService;
use App\Services\FollowService;
use App\Services\GroupService;
use App\Services\ImageService;
use App\Services\PostService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The profile page, and its owner's edits made right on it. The edit endpoints are plain JSON,
 * deliberately outside the Inertia request/response cycle: a page visit would reset the post
 * list already scrolled past its first page via <InfiniteScroll> — same as FollowController.
 */
class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
        protected FollowService $followService,
        protected PostService $postService,
        protected GroupService $groupService,
        protected AchievementService $achievementService
    ) {}

    // Public like a group page: anyone can read, following is gated in the UI and by UserPolicy.
    public function show(Request $request, string $username): Response
    {
        // Throws ModelNotFoundException (rendered as 404) when no user has this username.
        $user = $this->profileService->getProfile($username);

        $viewerId = $request->user()?->id;
        $isSelf = $viewerId === $user->id;

        return Inertia::render('Users/Show', [
            'profile' => new UserProfileResource($user),
            'is_self' => $isSelf,
            'is_following' => $this->followService->isFollowing($viewerId, $user->id),
            'achievements' => AchievementResource::collection($this->achievementService->getUnlocked($user->id)),
            // A private group only when the viewer is a member too, same rule as the posts below.
            'groups' => $this->groupService->getMembershipsVisibleTo($user->id, $viewerId)
                ->map(fn ($group) => $group->only(['id', 'slug', 'name', 'is_private'])),
            'posts' => Inertia::scroll(fn () => PostResource::collection($this->postService->getAuthorPosts($user->id, $viewerId))),
            // What the owner's edit form needs; null for everyone else.
            'edit' => $isSelf ? [
                'status_emojis' => UpdateProfileRequest::STATUS_EMOJIS,
                'status_text_max' => UpdateProfileRequest::STATUS_TEXT_MAX_LENGTH,
                'bio_max' => UpdateProfileRequest::BIO_MAX_LENGTH,
                'avatar_max_kb' => UpdateAvatarRequest::MAX_SIZE_KB,
            ] : null,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile($request->user(), $request->validated());

        return response()->json($user->only(['status_emoji', 'status_text', 'bio']));
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $this->profileService->updateAvatar($request->user(), $request->file('avatar'));

        return response()->json(['avatar_url' => ImageService::url($user->avatar_url)]);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $this->profileService->removeAvatar($request->user());

        return response()->json(['avatar_url' => null]);
    }
}
