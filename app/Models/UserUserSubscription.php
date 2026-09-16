<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUserSubscription extends Model
{
    public $incrementing = false;

    protected $table = 'user_user_subscriptions';

    protected $fillable = [
        'user_follower_id',
        'user_author_id',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_follower_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_author_id');
    }
}