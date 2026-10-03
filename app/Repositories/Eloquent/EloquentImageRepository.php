<?php

namespace App\Repositories\Eloquent;

use App\Enums\ImageOwnerType;
use App\Models\Image;
use App\Repositories\Contracts\ImageRepositoryInterface;

class EloquentImageRepository implements ImageRepositoryInterface
{
    public function create(array $data): Image
    {
        return Image::create($data);
    }

    public function findForOwner(ImageOwnerType $ownerType, string $ownerId): ?Image
    {
        return Image::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->latest()
            ->first();
    }

    public function delete(Image $image): void
    {
        $image->delete();
    }
}
