<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class CategoryImportReview extends Model
{
    protected $guarded = [];

    protected $casts = ['reasons' => 'array', 'context' => 'array', 'confidence' => 'float'];
}
