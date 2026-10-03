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
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
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

    // Users who opened this post's page (`post_views`), each recorded once.
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