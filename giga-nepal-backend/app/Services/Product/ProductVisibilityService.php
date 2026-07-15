<?php

namespace App\Services\Product;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductVisibilityService
{
    public function __construct(private readonly ProductPublicationGate $publicationGate) {}

    public function publicProducts(): Builder
    {
        return $this->publicationGate->apply(DB::table('products'));
    }

    public function resolve(string|int $product): ?object
    {
        $query = $this->publicProducts();

        return is_numeric($product)
            ? $query->where('id', (int) $product)->first()
            : $query->where('slug', $product)->first();
    }
}
