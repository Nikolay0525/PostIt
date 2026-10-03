<?php

namespace App\Repositories\Contracts;

use App\Enums\ImageOwnerType;
use App\Models\Image;

interface ImageRepositoryInterface
{
    /**
     * @param  array{uploader_id: string, owner_type: ImageOwnerType, owner_id: string, file_name: string, file_extension: string, url: string, is_adult_image?: bool}  $data
     */
    public function create(array $data): Image;

    /**
     * For owners that have a single image (a user's avatar, a group's icon).
     */
    public function findForOwner(ImageOwnerType $ownerType, string $ownerId): ?Image;

    public function delete(Image $image): void;
}
