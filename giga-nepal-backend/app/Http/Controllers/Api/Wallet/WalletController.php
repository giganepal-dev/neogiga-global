<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Payments\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer store-credit wallet (auth: api.token). Read-only for customers;
 * balance changes happen server-side (refunds, admin adjustments, checkout).
 */
class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet ? (float) $wallet->balance : 0.0,
                'currency' => $wallet->currency ?? 'USD',
                'status' => $wallet->status ?? 'active',
            ],
        ]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if (!$wallet) {
            return response()->json(['success' => true, 'data' => ['data' => []]]);
        }

        return response()->json([
            'success' => true,
            'data' => $wallet->entries()->orderByDesc('id')->paginate(30),
        ]);
    }
}
