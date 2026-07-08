<?php

namespace App\Services\Bom;

use App\Models\Bom\BomProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomPricingService
{
    public function estimate(BomProject $project, array $overrides = []): array
    {
        $items = $project->items()->orderBy('priority')->get();
        $lines = [];
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($overrides['quantities'][$item->id] ?? $item->quantity ?? 1);
            $unitPrice = $this->unitPrice((int) $item->product_id);
            $lineTotal = round($unitPrice * $quantity, 2);
            $total += $lineTotal;

            $lines[] = [
                'bom_project_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'required_or_optional' => $item->required_or_optional,
            ];
        }

        return [
            'project_id' => $project->id,
            'currency' => config('app.currency', 'NPR'),
            'total' => round($total, 2),
            'items' => $lines,
            'price_note' => 'Server-side estimate only; final cart and checkout totals may change by seller, stock, tax, and shipping.',
        ];
    }

    private function unitPrice(int $productId): float
    {
        if ($productId <= 0) {
            return 0.0;
        }

        if (Schema::hasTable('marketplace_product_prices')) {
            $price = DB::table('marketplace_product_prices')
                ->where('product_id', $productId)
                ->where(function ($query) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->orderByDesc('id')
                ->value('price');

            if ($price !== null) {
                return (float) $price;
            }
        }

        if (Schema::hasTable('products')) {
            $product = DB::table('products')->where('id', $productId)->first(['price', 'sale_price']);

            if ($product) {
                return (float) ($product->sale_price ?? $product->price ?? 0);
            }
        }

        return 0.0;
    }
}
