<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCounters extends Model
{
    protected $table = 'user_counters';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'posts_created', 'comments_created', 'groups_connected',
        'reports_sent', 'positive_votes', 'negative_votes', 'karma',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}