<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon_path',
        'image_path',
        'sort_order',
        'is_active',
        'is_featured',
        'marketplace_visibility',
        'seo_meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'marketplace_visibility' => 'array',
        'seo_meta' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductCategoryTranslation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('category_parent_child_map'));
        static::deleted(fn () => Cache::forget('category_parent_child_map'));
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Single-query cached parent→child ID map for the entire category tree.
     * Avoids N+1 DB queries in descendant traversal — one query, cached 24h.
     */
    public static function getParentChildMap(): array
    {
        return Cache::remember('category_parent_child_map', now()->addDay(), function () {
            $map = [];
            foreach (static::select('id', 'parent_id')->get() as $cat) {
                $map[$cat->parent_id ?? 0][] = $cat->id;
            }
            return $map;
        });
    }

    /**
     * Return all descendant IDs recursively (all levels).
     * Uses cached parent→child map + in-memory BFS — zero DB queries on cache hit.
     */
    public function descendantIds(): array
    {
        $map = static::getParentChildMap();
        $ids = [];
        $queue = $map[$this->id] ?? [];

        while (! empty($queue)) {
            $childId = array_shift($queue);
            $ids[] = $childId;
            foreach ($map[$childId] ?? [] as $grandId) {
                $queue[] = $grandId;
            }
        }

        return $ids;
    }

    /**
     * Return [$this->id, ...all descendant IDs] for inclusive product queries.
     */
    public function idsIncludingSelfAndDescendants(): array
    {
        return array_merge([$this->id], $this->descendantIds());
    }

    /**
     * Count products across self + all descendants (deduplicated).
     */
    public function inclusiveProductCount(): int
    {
        $ids = $this->idsIncludingSelfAndDescendants();

        return Product::query()
            ->whereIn('category_id', $ids)
            ->published()
            ->count();
    }
}
