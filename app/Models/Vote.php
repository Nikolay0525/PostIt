<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    public $incrementing = false;

    protected $table = 'votes';
    protected $primaryKey = ['parent_id', 'user_id'];

    protected $fillable = [
        'parent_id',
        'user_id',
        'parent_type',
        'positive',
    ];

    protected function casts(): array
    {
        return [
            'parent_type' => 'integer',
            'positive' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}