<?php

namespace App\Models;

use App\Enums\VoteParentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends BaseEntity
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'group_id',
        'user_id',
        'title',
        'article',
        'slug',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    // Users who copied the link to this post (`post_shares`), each counted once.
    public function sharedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_shares', 'post_id', 'user_id');
    }

    // Users who have seen this post (`post_views`: in a list, its page, a vote), each recorded once.
    public function viewedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_views', 'post_id', 'user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'parent_id')
            ->where('parent_type', VoteParentType::Post);
    }
}