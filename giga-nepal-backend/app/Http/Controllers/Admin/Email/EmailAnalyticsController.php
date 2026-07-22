<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailAnalyticsController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_subscribers' => DB::table('email_subscribers')->count(),
            'active_subscribers' => DB::table('email_subscribers')->where('status', 'active')->count(),
            'total_campaigns' => DB::table('email_campaigns')->count(),
            'total_sent' => DB::table('email_delivery_events')->where('event_type', 'sent')->count(),
        ];
        return view('admin.email.analytics', compact('stats'));
    }

    public function campaigns(): View { return view('admin.email.analytics-campaigns'); }
    public function subscribers(): View { return view('admin.email.analytics-subscribers'); }
    public function delivery(): View { return view('admin.email.analytics-delivery'); }
    public function export(Request $request) { return back()->with('status', 'Export queued.'); }
}
