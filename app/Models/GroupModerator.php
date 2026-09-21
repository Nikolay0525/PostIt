<?php

namespace App\Models;

use App\Enums\GroupModeratorRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupModerator extends Model
{
    public $incrementing = false;

    protected $table = 'group_moderators';
    protected $primaryKey = ['user_id', 'group_id'];

    protected $fillable = [
        'user_id',
        'group_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => GroupModeratorRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}