<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seeded reference data keyed by ISO 639 code, deliberately not a BaseEntity (no UUID).
 */
class SpeakingLanguage extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'speaking_languages';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'native_name',
    ];

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_speaking_languages', 'language_code', 'user_id', 'code', 'id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'language_code');
    }
}
