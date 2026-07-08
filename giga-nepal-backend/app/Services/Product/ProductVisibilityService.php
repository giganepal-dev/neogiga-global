<?php

namespace App\Services\Product;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductVisibilityService
{
    public function publicProducts(): Builder
    {
        $query = DB::table('products');

        if (Schema::hasColumn('products', 'approval_status') && Schema::hasColumn('products', 'status')) {
            $query->where(function ($inner) {
                $inner->where('approval_status', 'approved')
                    ->orWhereIn('status', ['active', 'approved', 'published']);
            });
        } elseif (Schema::hasColumn('products', 'approval_status')) {
            $query->where('approval_status', 'approved');
        } elseif (Schema::hasColumn('products', 'status')) {
            $query->whereIn('status', ['active', 'approved', 'published']);
        }

        if (Schema::hasColumn('products', 'visibility_status')) {
            $query->whereIn('visibility_status', ['public', 'marketplace_only', 'quote_only']);
        }

        return $query;
    }

    public function resolve(string|int $product): ?object
    {
        $query = $this->publicProducts();

        return is_numeric($product)
            ? $query->where('id', (int) $product)->first()
            : $query->where('slug', $product)->first();
    }
}
