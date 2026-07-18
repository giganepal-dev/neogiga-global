<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $orders = DB::table('orders')
            ->where('user_id', $user?->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $rfqs = DB::table('rfq_requests')
            ->where('user_id', $user?->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('frontend.account.dashboard', [
            'user' => $user,
            'orders' => $orders,
            'rfqs' => $rfqs,
        ]);
    }
}
