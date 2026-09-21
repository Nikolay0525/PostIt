<?php

namespace App\Models;

use App\Enums\ImageOwnerType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends BaseEntity
{
    protected $table = 'images';

    protected $fillable = [
        'uploader_id',
        'owner_type',
        'owner_id',
        'file_name',
        'is_adult_image',
        'file_extension',
        'url',
        'moderation_status',
    ];

    protected function casts(): array
    {
        return [
            'owner_type' => ImageOwnerType::class,
            'is_adult_image' => 'boolean',
            'moderation_status' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}