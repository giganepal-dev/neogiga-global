<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;

class LmsProductLink extends BaseModel
{
    protected $table = 'lms_product_links';

    protected $fillable = [
        'lms_project_id',
        'product_id',
        'quantity',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_required' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
