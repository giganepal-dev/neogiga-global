<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function index(): View { return view('frontend.order.tracking', ['order' => null]); }

    public function lookup(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);
        $order = DB::table('orders')->where('order_number', $data['order_number'])->first();
        if ($order && $request->user() && $order->user_id === $request->user()->id) {
            // Authenticated own order — full access
        } elseif ($order && !empty($data['email']) && $order->email === $data['email']) {
            // Guest with matching email — limited access
        } elseif ($order && !$request->user()) {
            $order = null; // Require email verification for guest
        }
        return view('frontend.order.tracking', [
            'order' => $order,
            'lookup' => $data['order_number'],
            'error' => $order ? null : 'Order not found. Check your order number and email.',
        ]);
    }
}
