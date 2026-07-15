<?php

namespace App\Services\Product;

use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenericProductSuggestionService
{
    public function forProduct(int $productId, ?string $type = null): array
    {
        $direct = [];
        if (Schema::hasTable('product_generic_suggestions')) {
            $query = DB::table('product_generic_suggestions')
                ->where('source_product_id', $productId)
                ->where('is_active', true);
            if ($type) {
                $query->where('suggestion_type', $type);
            }
            if (Schema::hasColumn('product_generic_suggestions', 'suggested_product_id')) {
                $query->where(function ($suggestion) {
                    $suggestion->whereNull('suggested_product_id')
                        ->orWhereIn('suggested_product_id', Product::query()->published()->select('products.id'));
                });
            }
            $direct = $query->orderBy('priority')->limit(20)->get()->all();
        }

        if ($direct !== []) {
            return array_map(fn ($row) => (array) $row, $direct);
        }

        if (! Schema::hasTable('product_related_items') || ! Schema::hasColumn('product_related_items', 'product_id')) {
            return [];
        }

        $query = DB::table('product_related_items')
            ->where('product_id', $productId)
            ->whereIn('related_product_id', Product::query()->published()->select('products.id'));
        if (Schema::hasColumn('product_related_items', 'sort_order')) {
            $query->orderBy('sort_order');
        } else {
            $query->orderBy('id');
        }

        return $query
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'source_product_id' => $productId,
                'suggested_product_id' => $row->related_product_id ?? null,
                'suggestion_type' => $type ?? ($row->type ?? 'related'),
                'priority' => $row->sort_order ?? 100,
                'reason' => $row->reason ?? 'Related catalog item.',
                'is_active' => true,
            ])->all();
    }
}
