<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProductApprovalStatus extends BaseModel
{
    protected $table = 'product_approval_status';

    protected $fillable = [
        'product_id',
        'status', // draft, pending, approved, rejected, archived
        'reviewed_by',
        'rejection_reason',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
