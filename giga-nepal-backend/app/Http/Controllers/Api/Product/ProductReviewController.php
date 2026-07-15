<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing product reviews on the LIVE product_reviews schema (created
 * by the prod-side build, migration 2026_07_10_074500): public read of
 * APPROVED reviews + aggregate; authenticated submit always lands `pending`
 * for the admin moderation queue. Complements (not duplicates) the seller-side
 * submitReview and the admin updateProductReview action.
 */
class ProductReviewController extends Controller
{
    public function index(int $product): JsonResponse
    {
        abort_unless(Product::published()->whereKey($product)->exists(), 404);

        if (! Schema::hasTable('product_reviews')) {
            return response()->json(['data' => ['reviews' => [], 'count' => 0, 'avg_rating' => 0]]);
        }

        $reviews = DB::table('product_reviews')
            ->where('product_id', $product)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'reviewer_name', 'rating', 'title', 'body', 'use_case', 'is_verified_buyer', 'created_at']);

        $agg = DB::table('product_reviews')
            ->where('product_id', $product)
            ->where('status', 'approved')
            ->selectRaw('count(*) as count, coalesce(avg(rating),0) as avg_rating')
            ->first();

        return response()->json(['data' => [
            'reviews' => $reviews,
            'count' => (int) $agg->count,
            'avg_rating' => round((float) $agg->avg_rating, 1),
        ]]);
    }

    public function store(Request $request, int $product): JsonResponse
    {
        abort_unless(Schema::hasTable('product_reviews'), 503);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'use_case' => ['nullable', 'string', 'max:190'],
        ]);

        abort_unless(Product::published()->where('id', $product)->exists(), 404);

        $user = $request->user();

        // Verified buyer = has a delivered order containing this product.
        $verified = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.user_id', $user->id)
            ->where('oi.product_id', $product)
            ->where('o.status', 'delivered')
            ->exists();

        DB::table('product_reviews')->updateOrInsert(
            ['product_id' => $product, 'user_id' => $user->id],
            [
                'reviewer_name' => $user->name,
                'reviewer_email' => $user->email,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'use_case' => $data['use_case'] ?? null,
                'is_verified_buyer' => $verified,
                'status' => 'pending', // moderation queue, always
                'moderated_by' => null,
                'moderated_at' => null,
                'metadata' => json_encode(['channel' => 'customer_api']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['data' => ['status' => 'pending review']], 201);
    }
}
