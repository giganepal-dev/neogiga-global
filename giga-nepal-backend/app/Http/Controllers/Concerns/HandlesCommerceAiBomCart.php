<?php

namespace App\Http\Controllers\Concerns;

use App\Services\CommerceAi\CommerceAiBomCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesCommerceAiBomCart
{
    protected function addCommerceAiBomToCart(Request $request, CommerceAiBomCartService $cart): JsonResponse
    {
        if (! $request->user() || ! $request->user()->hasPermission('cart.manage')) {
            return $this->error('Forbidden.', 403);
        }

        $data = $request->validate([
            'bom_result_id' => ['required', 'integer', 'exists:commerce_ai_bom_results,id'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer', 'exists:marketplaces,id'],
        ]);
        $result = $cart->addToCart(
            $request->user(),
            $data['bom_result_id'],
            $data['marketplace_id'] ?? null,
        );

        if (! $result) {
            return $this->error('AI BOM result not found.', 404);
        }

        if ($result['marketplace_conflict'] ?? false) {
            return $this->error('Your active cart belongs to another marketplace. Complete or clear it before changing marketplace.', 409);
        }

        if ($result['added_count'] === 0 && $result['already_added_count'] === 0) {
            return $this->error('No AI BOM recommendations are currently purchasable.', 422);
        }

        return $this->success($result, $result['added_count'] > 0 ? 201 : 200);
    }
}
