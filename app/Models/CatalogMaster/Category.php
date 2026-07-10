<?php

namespace App\Models\CatalogMaster;

use App\Models\User;
use App\Models\CatalogImport\CatalogSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'path',
        'depth',
        'position',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'icon_path',
        'is_featured',
        'is_visible',
        'marketplace_visibility',
        'lms_topic_id',
        'status',
        'data_quality_score',
        'reviewed_at',
        'reviewed_by',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'marketplace_visibility' => 'array',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'data_quality_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_MERGED = 'merged';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(CategoryAlias::class);
    }

    public function externalMappings(): HasMany
    {
        return $this->hasMany(CategoryExternalMapping::class);
    }

    public function attributeGroups(): HasMany
    {
        return $this->hasMany(CategoryAttributeGroup::class);
    }

    public function categoryAttributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Get full category path as array of names
     */
    public function getPathNamesAttribute(): array
    {
        $names = [];
        $current = $this;
        
        while ($current) {
            array_unshift($names, $current->name);
            $current = $current->parent;
        }
        
        return $names;
    }

    /**
     * Generate materialized path
     */
    public static function generatePath(?int $parentId, int $currentId): string
    {
        if (!$parentId) {
            return '/' . $currentId . '/';
        }
        
        $parent = self::find($parentId);
        return ($parent ? $parent->path : '/') . $currentId . '/';
    }

    /**
     * Calculate depth based on path
     */
    public static function calculateDepth(string $path): int
    {
        $segments = array_filter(explode('/', $path));
        return count($segments) - 1; // Subtract 1 because root is depth 0
    }
}
