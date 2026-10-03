<?php

namespace App\Services;

use App\Enums\ImageOwnerType;
use App\Models\Image;
use App\Repositories\Contracts\ImageRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Keeps an uploaded file and its `images` row together: the file goes to the public disk under a
 * random name, the row records who uploaded it and what it belongs to (for moderation later).
 * `images.url` holds the path on the disk, not an absolute URL, so a change of APP_URL or of
 * the disk doesn't leave stale links in the database; url() turns it into a link when rendering.
 *
 * The file is stored as uploaded — no resizing or re-encoding (the GD extension isn't enabled).
 * The caller validates the type and size first.
 */
class ImageService
{
    public const DISK = 'public';

    public function __construct(
        protected ImageRepositoryInterface $imageRepository
    ) {}

    public function store(UploadedFile $file, string $directory, string $uploaderId, ImageOwnerType $ownerType, string $ownerId): Image
    {
        // extension() is guessed from the file's contents, not taken from the client's file name.
        $extension = $file->extension();
        $fileName = (string) Str::uuid();
        $path = $file->storeAs($directory, "{$fileName}.{$extension}", self::DISK);

        try {
            return $this->imageRepository->create([
                'uploader_id' => $uploaderId,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'file_name' => $fileName,
                'file_extension' => $extension,
                'url' => $path,
            ]);
        } catch (Throwable $e) {
            // No row means nothing will ever point at the file: don't leave it behind.
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }
    }

    public function delete(Image $image): void
    {
        $this->imageRepository->delete($image);

        Storage::disk(self::DISK)->delete($image->url);
    }

    public function findForOwner(ImageOwnerType $ownerType, string $ownerId): ?Image
    {
        return $this->imageRepository->findForOwner($ownerType, $ownerId);
    }

    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
