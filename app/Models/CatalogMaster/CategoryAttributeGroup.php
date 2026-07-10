<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryAttributeGroup extends Model
{
    use HasFactory;

    protected $table = 'category_attribute_groups';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'position',
        'is_visible',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class, 'attribute_group_id');
    }
}
