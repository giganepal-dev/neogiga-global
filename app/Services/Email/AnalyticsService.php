<?php

namespace App\Services\Email;

use App\Models\EmailCampaign;
use App\Models\EmailDeliveryEvent;
use App\Models\EmailClickEvent;
use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function getDeliveryRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $sent = EmailDeliveryEvent::where('event_type', 'sent')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($sent === 0) return 0.0;

        $delivered = EmailDeliveryEvent::where('event_type', 'delivered')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return round(($delivered / $sent) * 100, 2);
    }

    public function getOpenRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $delivered = EmailDeliveryEvent::where('event_type', 'delivered')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($delivered === 0) return 0.0;

        $opened = EmailDeliveryEvent::where('event_type', 'opened')
            ->whereBetween('created_at', [$start, $end])
            ->distinct('recipient_id')
            ->count('recipient_id');

        return round(($opened / $delivered) * 100, 2);
    }

    public function getClickRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $delivered = EmailDeliveryEvent::where('event_type', 'delivered')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($delivered === 0) return 0.0;

        $clicked = EmailClickEvent::whereBetween('created_at', [$start, $end])
            ->distinct('subscriber_id')
            ->count('subscriber_id');

        return round(($clicked / $delivered) * 100, 2);
    }

    public function getBounceRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $sent = EmailDeliveryEvent::where('event_type', 'sent')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($sent === 0) return 0.0;

        $bounced = EmailDeliveryEvent::whereIn('event_type', ['soft_bounced', 'hard_bounced'])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return round(($bounced / $sent) * 100, 2);
    }

    public function getUnsubscribeRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $delivered = EmailDeliveryEvent::where('event_type', 'delivered')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($delivered === 0) return 0.0;

        $unsubscribed = EmailDeliveryEvent::where('event_type', 'unsubscribed')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return round(($unsubscribed / $delivered) * 100, 4);
    }

    public function getComplaintRate(array $dateRange): float
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $delivered = EmailDeliveryEvent::where('event_type', 'delivered')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($delivered === 0) return 0.0;

        $complained = EmailDeliveryEvent::where('event_type', 'complained')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return round(($complained / $delivered) * 100, 4);
    }

    public function getSubscriberGrowth(array $dateRange): array
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        $growth = [];
        $current = $start->copy();

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $count = EmailSubscriber::whereDate('created_at', $date)
                ->where('status', 'subscribed')
                ->count();
            
            $growth[] = [
                'date' => $date,
                'count' => $count
            ];

            $current->addDay();
        }

        return $growth;
    }

    public function getSubscribersByCountry(): array
    {
        return EmailSubscriber::select('country_code', DB::raw('count(*) as count'))
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getSubscribersByGroup(): array
    {
        return DB::table('email_group_subscriber')
            ->join('email_groups', 'email_group_subscriber.group_id', '=', 'email_groups.id')
            ->select('email_groups.name', DB::raw('count(*) as count'))
            ->groupBy('email_groups.id', 'email_groups.name')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getSubscribersByType(): array
    {
        return EmailSubscriber::select('subscriber_type', DB::raw('count(*) as count'))
            ->whereNotNull('subscriber_type')
            ->groupBy('subscriber_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getTopCampaigns(int $limit = 10, array $dateRange = null): array
    {
        $query = EmailCampaign::withCount(['recipients as total_sent' => function ($q) {
            $q->where('status', 'sent');
        }])
        ->withCount(['deliveryEvents as total_opened' => function ($q) {
            $q->where('event_type', 'opened');
        }])
        ->withCount(['deliveryEvents as total_clicked' => function ($q) {
            $q->where('event_type', 'clicked');
        }])
        ->where('status', 'completed')
        ->orderByDesc('total_sent')
        ->limit($limit);

        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        return $query->get()->map(function ($campaign) {
            $openRate = $campaign->total_sent > 0 
                ? round(($campaign->total_opened / $campaign->total_sent) * 100, 2) 
                : 0;
            $clickRate = $campaign->total_sent > 0 
                ? round(($campaign->total_clicked / $campaign->total_sent) * 100, 2) 
                : 0;

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'sent_at' => $campaign->sent_at,
                'total_sent' => $campaign->total_sent,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
            ];
        })->toArray();
    }

    public function getProviderUsage(array $dateRange): array
    {
        return DB::table('email_delivery_events')
            ->select('provider', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('provider')
            ->get()
            ->toArray();
    }

    public function getDetailedAnalytics(array $dateRange, array $filters = []): array
    {
        $query = EmailDeliveryEvent::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        $events = $query->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type');

        return [
            'sent' => $events->get('sent', 0),
            'delivered' => $events->get('delivered', 0),
            'opened' => $events->get('opened', 0),
            'clicked' => $events->get('clicked', 0),
            'soft_bounced' => $events->get('soft_bounced', 0),
            'hard_bounced' => $events->get('hard_bounced', 0),
            'complained' => $events->get('complained', 0),
            'unsubscribed' => $events->get('unsubscribed', 0),
        ];
    }
}
