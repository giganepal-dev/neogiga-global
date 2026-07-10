<?php

namespace App\Models\CatalogMaster;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brands';

    protected $fillable = [
        'manufacturer_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'official_website',
        'status',
        'data_quality_score',
        'reviewed_at',
        'reviewed_by',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'data_quality_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING_REVIEW = 'pending_review';

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function externalIds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BrandExternalId::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public static function generateSlug(string $name): string
    {
        return str()->slug($name);
    }
}
