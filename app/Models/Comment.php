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
        'edited_at',
        'hidden_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
            'edited_at' => 'datetime',
            'hidden_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    // Under a moderation case: shown as "hidden", not deleted.
    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    // Taken down by a moderation verdict. The text is kept, but only its author may read it.
    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    // Hidden or removed: its text and author are not shown to the page.
    public function isConcealed(): bool
    {
        return $this->isHidden() || $this->isRemoved();
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