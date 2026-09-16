<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCounters extends Model
{
    protected $table = 'user_counters';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'posts_created', 'comments_created', 'groups_connected',
        'reports_sent', 'positive_votes', 'negative_votes', 'karma',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}