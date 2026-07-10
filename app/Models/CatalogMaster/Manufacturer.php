<?php

namespace App\Models\CatalogMaster;

use App\Models\User;
use App\Models\CatalogImport\CatalogSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturers';

    protected $fillable = [
        'legal_name',
        'display_name',
        'slug',
        'aliases',
        'official_website',
        'logo_path',
        'country_code',
        'status',
        'successor_manufacturer_id',
        'source',
        'external_source_id',
        'source_url',
        'authorization_status',
        'data_quality_score',
        'reviewed_at',
        'reviewed_by',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'aliases' => 'array',
        'data_quality_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MERGED = 'merged';
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /**
     * Authorization status constants
     */
    public const AUTHORIZED = 'authorized';
    public const UNAUTHORIZED = 'unauthorized';
    public const UNKNOWN = 'unknown';
    public const RESTRICTED = 'restricted';

    public function aliases(): HasMany
    {
        return $this->hasMany(ManufacturerAlias::class);
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(ManufacturerExternalId::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function sourceRecords(): HasMany
    {
        return $this->hasMany(ManufacturerSourceRecord::class);
    }

    public function mergeCandidates1(): HasMany
    {
        return $this->hasMany(ManufacturerMergeCandidate::class, 'manufacturer_id_1');
    }

    public function mergeCandidates2(): HasMany
    {
        return $this->hasMany(ManufacturerMergeCandidate::class, 'manufacturer_id_2');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'successor_manufacturer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for active manufacturers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for authorized manufacturers
     */
    public function scopeAuthorized($query)
    {
        return $query->where('authorization_status', self::AUTHORIZED);
    }

    /**
     * Generate slug from display name
     */
    public static function generateSlug(string $name): string
    {
        return str()->slug($name);
    }

    /**
     * Normalize manufacturer name for matching
     */
    public static function normalizeName(string $name): string
    {
        // Remove common suffixes
        $suffixes = [' Inc.', ' Inc', ' Ltd.', ' Ltd', ' LLC', ' Corp.', ' Corp', ' Company', ' Co.', ' Co'];
        $normalized = str_replace($suffixes, '', $name);
        
        // Convert to lowercase and trim
        $normalized = strtolower(trim($normalized));
        
        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return $normalized;
    }
}
