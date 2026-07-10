<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryAlias extends Model
{
    use HasFactory;

    protected $table = 'category_aliases';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'alias',
        'source',
        'confidence_score',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
