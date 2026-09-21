<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends BaseEntity
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'text',
        'url',
        'is_read',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'type' => NotificationType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}