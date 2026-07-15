<?php

namespace App\Models\Bom;

use App\Models\Marketplace\Product;
use Illuminate\Database\Eloquent\Model;

class BomProjectItem extends Model
{
    protected $fillable = ['bom_project_id', 'product_id', 'category_id', 'name', 'required_or_optional', 'quantity', 'reason', 'substitute_allowed', 'priority', 'notes'];

    protected $casts = ['substitute_allowed' => 'boolean'];

    public function scopePubliclyAvailable($query)
    {
        return $query->where(function ($item) {
            $item->whereNull('product_id')
                ->orWhereIn('product_id', Product::query()->published()->select('products.id'));
        });
    }
}
