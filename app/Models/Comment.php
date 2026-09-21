<?php

namespace App\Models;

use App\Enums\VoteParentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends BaseEntity
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'post_id',
        'parent_id',
        'user_id',
        'text',
        'is_deleted',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Parent comment for nested threads
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Replies to this comment
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'parent_id')
            ->where('parent_type', VoteParentType::Comment);
    }
}