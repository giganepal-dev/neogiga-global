<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    use ApiResponses;

    public function overview(): JsonResponse
    {
        return $this->success(['revenue' => DB::table('orders')->sum('grand_total'), 'orders' => DB::table('orders')->count(), 'newsletter_subscribers' => DB::table('newsletter_subscribers')->count(), 'customers' => DB::table('customer_profiles')->count(), 'abandoned_cart_value' => DB::table('abandoned_carts')->sum('cart_total')]);
    }

    public function proxy(string $type = ''): JsonResponse
    {
        return $this->success(['widget' => $type, 'status' => 'ready']);
    }
}
