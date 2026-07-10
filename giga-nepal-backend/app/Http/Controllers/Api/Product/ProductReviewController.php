<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Product reviews: public read (approved only) + authenticated submit.
 * Every submission lands as `pending` — nothing goes public without admin
 * moderation (/admin/reviews). One review per user per product; resubmitting
 * updates the user's own review and returns it to the moderation queue.
 */
class ProductReviewController extends Controller
{
    public function index(int $product): JsonResponse
    {
        $reviews = DB::table('product_reviews as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.product_id', $product)
            ->where('r.status', 'approved')
            ->orderByDesc('r.id')
            ->limit(50)
            ->get(['r.id', 'r.rating', 'r.title', 'r.body', 'r.created_at', 'u.name as reviewer']);

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
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'order_id' => ['nullable', 'integer'],
        ]);

        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        // Verified-purchase link only when the order really belongs to this user.
        $orderId = null;
        if (! empty($data['order_id'])) {
            $orderId = DB::table('orders')->where('id', $data['order_id'])
                ->where('user_id', $request->user()->id)->value('id');
        }

        DB::table('product_reviews')->updateOrInsert(
            ['product_id' => $product, 'user_id' => $request->user()->id],
            [
                'order_id' => $orderId,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'status' => 'pending', // moderation queue, always
                'moderated_by' => null,
                'moderated_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['data' => ['status' => 'pending review']], 201);
    }
}
