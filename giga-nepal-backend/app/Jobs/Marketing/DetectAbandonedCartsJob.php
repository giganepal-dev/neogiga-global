<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DetectAbandonedCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = []) {}

    public function handle(): void
    {
        foreach (['carts', 'cart_items', 'abandoned_carts', 'abandoned_cart_items'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $minutes = max(15, min(10080, (int) ($this->payload['inactive_minutes'] ?? 120)));
        $limit = max(10, min(1000, (int) ($this->payload['limit'] ?? 200)));
        $cutoff = now()->subMinutes($minutes);

        $carts = DB::table('carts as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.is_active', true)
            ->where('c.updated_at', '<=', $cutoff)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('cart_items as ci')
                    ->whereColumn('ci.cart_id', 'c.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('abandoned_carts as ac')
                    ->whereColumn('ac.cart_id', 'c.id');
            })
            ->select('c.*', 'u.email as user_email')
            ->orderBy('c.updated_at')
            ->limit($limit)
            ->get();

        foreach ($carts as $cart) {
            DB::transaction(function () use ($cart) {
                $abandonedCartId = DB::table('abandoned_carts')->insertGetId([
                    'cart_id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'email' => $cart->user_email,
                    'currency_code' => $cart->currency_code ?? 'USD',
                    'cart_total' => $cart->grand_total ?? $cart->subtotal ?? 0,
                    'status' => 'open',
                    'abandoned_at' => $cart->updated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $items = DB::table('cart_items')
                    ->where('cart_id', $cart->id)
                    ->get()
                    ->map(fn ($item) => [
                        'abandoned_cart_id' => $abandonedCartId,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->variant_id ?? null,
                        'name' => $this->productName($item->product_id),
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all();

                if ($items !== []) {
                    DB::table('abandoned_cart_items')->insert($items);
                }
            });
        }
    }

    private function productName(?int $productId): ?string
    {
        if (! $productId || ! Schema::hasTable('products')) {
            return null;
        }

        return DB::table('products')->where('id', $productId)->value('name');
    }
}
