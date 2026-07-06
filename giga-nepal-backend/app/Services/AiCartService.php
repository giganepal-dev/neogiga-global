<?php

namespace App\Services;

use App\Models\AiBomBuild;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiCartService
{
    public function addBomToCart(AiBomBuild $bomBuild, User $user): Cart
    {
        return DB::transaction(function () use ($bomBuild, $user) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'marketplace_id' => 1, // Default to global, will be resolved properly
                    'status' => 'active',
                ]
            );

            foreach ($bomBuild->items as $item) {
                if (!$item->product_id) {
                    continue;
                }

                $existingItem = $cart->items()
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $item->quantity);
                } else {
                    $cart->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'added_by_ai' => true,
                        'ai_session_id' => $bomBuild->session_id,
                        'ai_bom_build_id' => $bomBuild->id,
                    ]);
                }
            }

            $cart->calculateTotal();
            
            return $cart->fresh();
        });
    }

    public function addRecommendationToCart(
        int $productId,
        User $user,
        int $quantity = 1,
        ?int $aiSessionId = null
    ): CartItem {
        return DB::transaction(function () use ($productId, $user, $quantity, $aiSessionId) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'marketplace_id' => 1,
                    'status' => 'active',
                ]
            );

            $existingItem = $cart->items()
                ->where('product_id', $productId)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $quantity);
                return $existingItem->fresh();
            }

            return $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_by_ai' => $aiSessionId !== null,
                'ai_session_id' => $aiSessionId,
            ]);
        });
    }

    public function getCartWithAIItems(User $user): ?Cart
    {
        return Cart::where('user_id', $user->id)
            ->with(['items.product', 'items.aiBomBuild'])
            ->latest()
            ->first();
    }

    public function clearAICartItems(User $user, int $sessionId): int
    {
        $cart = Cart::where('user_id', $user->id)->latest()->first();
        
        if (!$cart) {
            return 0;
        }

        return $cart->items()
            ->where('ai_session_id', $sessionId)
            ->delete();
    }
}
