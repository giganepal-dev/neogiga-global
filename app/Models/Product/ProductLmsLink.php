<?php

namespace App\Models\Product;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Lms\LmsProject;

class ProductLmsLink extends BaseModel
{
    protected $table = 'product_lms_links';

    protected $fillable = [
        'product_id',
        'lms_project_id',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }
}
