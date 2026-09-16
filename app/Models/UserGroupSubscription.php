<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGroupSubscription extends Model
{
    public $incrementing = false;
    const UPDATED_AT = null;

    protected $table = 'user_group_subscriptions';
    protected $primaryKey = ['user_id', 'group_id'];

    protected $fillable = [
        'user_id',
        'group_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}