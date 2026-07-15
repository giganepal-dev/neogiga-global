<?php

namespace App\Services\CommerceAi;

use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommerceAiRecommendationService
{
    public function enrich(array $items): array
    {
        return array_map(function (array $item) {
            $match = $this->findProduct($item['name']);
            $item['product_id'] = $match?->id;
            $item['availability_status'] = $match ? 'catalog_match_stock_not_verified' : 'generic_suggestion_product_not_matched';
            $item['datasheet_url'] = $match?->datasheet_url ?? null;
            $item['warranty_note'] = $match ? 'Use seller/product warranty terms at checkout.' : 'Warranty depends on selected seller/product.';

            return $item;
        }, $items);
    }

    private function findProduct(string $name): ?object
    {
        if (! Schema::hasTable('products')) {
            return null;
        }

        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $query = Product::query()->published()->where('name', $operator, '%'.$name.'%');

        if (Schema::hasColumn('products', 'status')) {
            $query->orderByRaw("case when status = 'approved' then 0 else 1 end");
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('products', 'datasheet_url')) {
            $columns[] = 'datasheet_url';
        }

        return $query->orderBy('id')->first($columns);
    }
}
