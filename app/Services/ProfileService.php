<?php

namespace App\Services;

use App\Enums\ImageOwnerType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * The user's public profile. `users.avatar_url` mirrors the path of the user's current avatar
 * image, so lists of posts/comments can show avatars without a join on `images`.
 */
class ProfileService
{
    private const AVATAR_DIRECTORY = 'avatars';

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected ImageService $imageService
    ) {}

    /**
     * @throws ModelNotFoundException when no user has this username
     */
    public function getProfile(string $username): User
    {
        return $this->userRepository->findForProfile($username)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$username]);
    }

    /**
     * @param  array{status_emoji?: ?string, status_text?: ?string, bio?: ?string}  $fields  only the fields to change
     */
    public function updateProfile(User $user, array $fields): User
    {
        $allowed = array_intersect_key($fields, array_flip(['status_emoji', 'status_text', 'bio']));

        return $allowed === [] ? $user : $this->userRepository->update($user, $allowed);
    }

    /**
     * Replaces the avatar. The old image is removed only once the new one is saved, so a failed
     * upload leaves the previous avatar in place.
     */
    public function updateAvatar(User $user, UploadedFile $file): User
    {
        $previous = $this->imageService->findForOwner(ImageOwnerType::User, $user->id);
        $image = $this->imageService->store($file, self::AVATAR_DIRECTORY, $user->id, ImageOwnerType::User, $user->id);

        try {
            $user = $this->userRepository->update($user, ['avatar_url' => $image->url]);
        } catch (Throwable $e) {
            $this->imageService->delete($image);

            throw $e;
        }

        if ($previous !== null) {
            $this->imageService->delete($previous);
        }

        return $user;
    }

    /**
     * Back to the default picture.
     */
    public function removeAvatar(User $user): User
    {
        $user = $this->userRepository->update($user, ['avatar_url' => null]);

        $current = $this->imageService->findForOwner(ImageOwnerType::User, $user->id);

        if ($current !== null) {
            $this->imageService->delete($current);
        }

        return $user;
    }
}
