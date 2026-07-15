<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQuestion extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'parent_id',
        'question',
        'answer',
        'answered_by',
        'answered_at',
        'is_accepted_answer',
        'source',
        'metadata',
    ];

    protected $casts = [
        'is_accepted_answer' => 'boolean',
        'answered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'parent_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ProductQuestion::class, 'parent_id');
    }

    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeQuestionsOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeAcceptedAnswers($query)
    {
        return $query->where('is_accepted_answer', true);
    }

    public function markAsAcceptedAnswer()
    {
        // Unset other accepted answers for this question
        ProductQuestion::where('parent_id', $this->parent_id ?? $this->id)
            ->update(['is_accepted_answer' => false]);
        
        $this->update(['is_accepted_answer' => true]);
    }

    public function isFromManufacturerOrDistributor(): bool
    {
        if (! $this->answered_by) {
            return false;
        }

        $answerer = User::find($this->answered_by);
        if (! $answerer) {
            return false;
        }

        return $answerer->hasRole('manufacturer') || $answerer->hasRole('distributor');
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'customer' => 'Customer Question',
            'manufacturer' => 'Manufacturer Response',
            'distributor' => 'Distributor Response',
            'engineer' => 'Engineering Team',
            default => 'Question',
        };
    }
}
