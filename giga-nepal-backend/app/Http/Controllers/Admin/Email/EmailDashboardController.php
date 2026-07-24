<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_subscribers' => DB::table('email_subscribers')->count(),
            'active_subscribers' => DB::table('email_subscribers')->where('status', 'active')->count(),
            'total_groups' => DB::table('email_groups')->count(),
            'total_segments' => DB::table('email_segments')->count(),
            'total_campaigns' => DB::table('email_campaigns')->count(),
            'sent_campaigns' => DB::table('email_campaigns')->where('status', 'sent')->count(),
            'scheduled_campaigns' => DB::table('email_campaigns')->where('status', 'scheduled')->count(),
            'draft_campaigns' => DB::table('email_campaigns')->where('status', 'draft')->count(),
            'total_sent' => DB::table('email_delivery_logs')->where('status', 'sent')->count(),
            'total_opened' => DB::table('email_delivery_logs')->where('status', 'opened')->count(),
            'total_clicked' => DB::table('email_delivery_logs')->where('status', 'clicked')->count(),
            'total_bounced' => DB::table('email_delivery_logs')->where('status', 'bounced')->count(),
            'suppressed_count' => DB::table('email_suppressions_extension')->count(),
        ];

        $recentCampaigns = DB::table('email_campaigns')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentSubscribers = DB::table('email_subscribers')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.email.dashboard', compact('stats', 'recentCampaigns', 'recentSubscribers'));
    }
}
