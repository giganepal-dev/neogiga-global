<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use App\Models\EmailSegment;
use App\Models\EmailTemplate;
use App\Models\EmailCampaign;
use App\Models\EmailImport;
use App\Models\EmailSuppression;
use App\Services\Email\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailDashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->middleware(['permission:email.dashboard.view']);
    }

    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_subscribers' => EmailSubscriber::count(),
            'subscribed' => EmailSubscriber::where('status', 'subscribed')->count(),
            'unsubscribed' => EmailSubscriber::where('status', 'unsubscribed')->count(),
            'suppressed' => EmailSuppression::count(),
            'total_campaigns' => EmailCampaign::count(),
            'campaigns_sent' => EmailCampaign::where('status', 'completed')->count(),
            'campaigns_sending' => EmailCampaign::where('status', 'sending')->count(),
            'total_emails_sent' => DB::table('email_delivery_events')
                ->where('event_type', 'sent')
                ->count(),
            'delivery_rate' => $this->analyticsService->getDeliveryRate($dateRange),
            'open_rate' => $this->analyticsService->getOpenRate($dateRange),
            'click_rate' => $this->analyticsService->getClickRate($dateRange),
            'bounce_rate' => $this->analyticsService->getBounceRate($dateRange),
            'unsubscribe_rate' => $this->analyticsService->getUnsubscribeRate($dateRange),
            'complaint_rate' => $this->analyticsService->getComplaintRate($dateRange),
        ];

        $subscriberGrowth = $this->analyticsService->getSubscriberGrowth($dateRange);
        $countryDistribution = $this->analyticsService->getSubscribersByCountry();
        $groupDistribution = $this->analyticsService->getSubscribersByGroup();
        $typeDistribution = $this->analyticsService->getSubscribersByType();
        
        $topCampaigns = $this->analyticsService->getTopCampaigns(10, $dateRange);
        $recentImports = EmailImport::latest()->limit(5)->get();
        $providerStats = $this->analyticsService->getProviderUsage($dateRange);

        return view('admin.email.dashboard', compact(
            'stats',
            'subscriberGrowth',
            'countryDistribution',
            'groupDistribution',
            'typeDistribution',
            'topCampaigns',
            'recentImports',
            'providerStats',
            'dateRange'
        ));
    }

    public function subscribers(Request $request)
    {
        $this->middleware(['permission:email.subscribers.view']);
        
        $query = EmailSubscriber::with(['groups', 'region']);
        
        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('country_code')) {
            $query->where('country_code', $request->country_code);
        }
        
        if ($request->filled('type')) {
            $query->where('subscriber_type', $request->type);
        }

        $subscribers = $query->latest()->paginate(25);
        $groups = EmailGroup::all();
        
        return view('admin.email.subscribers.index', compact('subscribers', 'groups'));
    }

    public function groups(Request $request)
    {
        $this->middleware(['permission:email.groups.manage']);
        
        $query = EmailGroup::withCount('subscribers');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $groups = $query->latest()->paginate(25);
        
        return view('admin.email.groups.index', compact('groups'));
    }

    public function segments(Request $request)
    {
        $this->middleware(['permission:email.segments.manage']);
        
        $segments = EmailSegment::withCount('subscribers')->latest()->paginate(25);
        
        return view('admin.email.segments.index', compact('segments'));
    }

    public function campaigns(Request $request)
    {
        $this->middleware(['permission:email.campaigns.create']);
        
        $query = EmailCampaign::with(['sender', 'groups']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $campaigns = $query->latest()->paginate(25);
        
        return view('admin.email.campaigns.index', compact('campaigns'));
    }

    public function imports(Request $request)
    {
        $this->middleware(['permission:email.subscribers.import']);
        
        $query = EmailImport::with('user');
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $imports = $query->latest()->paginate(25);
        
        return view('admin.email.imports.index', compact('imports'));
    }

    public function analytics(Request $request)
    {
        $this->middleware(['permission:email.analytics.view']);
        
        $dateRange = $this->getDateRange($request);
        $filters = $request->only(['campaign_id', 'country_code', 'group_id', 'provider']);
        
        $detailedStats = $this->analyticsService->getDetailedAnalytics($dateRange, $filters);
        
        return view('admin.email.analytics.index', compact('detailedStats', 'dateRange', 'filters'));
    }

    private function getDateRange(Request $request): array
    {
        $start = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $end = $request->input('end_date', now()->format('Y-m-d'));
        
        return ['start' => $start, 'end' => $end];
    }
}
