<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCertificate extends Model
{
    protected $fillable = [
        'product_id',
        'certificate_name',
        'certificate_number',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'file_path',
        'remarks',
        'is_verified',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && $this->is_verified;
    }
}
