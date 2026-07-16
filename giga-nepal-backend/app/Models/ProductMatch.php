<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'query_mpn',
        'normalized_mpn',
        'product_id',
        'confidence_score',
        'match_algorithm',
        'hit_count',
        'last_matched_at',
    ];

    protected $casts = [
        'last_matched_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function incrementHitCount(): void
    {
        $this->increment('hit_count');
        $this->update(['last_matched_at' => now()]);
    }
}
