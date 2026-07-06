<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRating extends BaseModel
{
    protected $table = 'vendor_ratings';

    protected $fillable = [
        'vendor_id', 'user_id', 'order_id', 'rating',
        'title', 'comment', 'is_verified_purchase',
        'helpful_count', 'response', 'responded_at', 'metadata'
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'is_verified_purchase' => 'boolean',
        'responded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\Order\Order::class);
    }
}
