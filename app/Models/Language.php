<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Language Model
 * 
 * Represents a language with localization settings.
 */
class Language extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'flag_emoji',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get countries where this language is spoken.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_language')
            ->withPivot(['is_official', 'is_primary', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Scope to get only active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find language by code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', strtolower($code))->first();
    }
}
