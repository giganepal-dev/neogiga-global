<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_campaigns');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(20);

        $statuses = DB::table('email_campaigns')->distinct()->pluck('status')->filter()->sort()->values();

        return view('admin.email.campaigns.index', compact('campaigns', 'statuses'));
    }

    public function create(): View
    {
        $templates = DB::table('email_templates')->where('is_active', true)->orderBy('name')->get();
        $groups = DB::table('email_groups')->orderBy('name')->get();
        $segments = DB::table('email_segments')->orderBy('name')->get();
        $senders = DB::table('email_senders_extension')->where('is_verified', true)->orderBy('sender_name')->get();

        return view('admin.email.campaigns.create', compact('templates', 'groups', 'segments', 'senders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'template_id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'sender_id' => ['nullable', 'integer'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
            'segment_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'preview_text' => ['nullable', 'string', 'max:255'],
        ]);

        $campaignId = DB::table('email_campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'template_id' => $data['template_id'] ?? null,
            'sender_id' => $data['sender_id'] ?? null,
            'group_ids' => json_encode($data['group_ids'] ?? []),
            'segment_id' => $data['segment_id'] ?? null,
            'status' => 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'preview_text' => $data['preview_text'] ?? null,
            'recipient_count' => 0,
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
            'bounce_count' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaignId}")->with('status', 'Campaign created.');
    }

    public function show(int $campaign): View
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $template = $row->template_id ? DB::table('email_templates')->find($row->template_id) : null;
        $sender = $row->sender_id ? DB::table('email_senders_extension')->find($row->sender_id) : null;
        $segment = $row->segment_id ? DB::table('email_segments')->find($row->segment_id) : null;

        $deliveryStats = [
            'sent' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'sent')->count(),
            'delivered' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'delivered')->count(),
            'opened' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'opened')->count(),
            'clicked' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'clicked')->count(),
            'bounced' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'bounced')->count(),
            'unsubscribed' => DB::table('email_delivery_logs')->where('campaign_id', $campaign)->where('status', 'unsubscribed')->count(),
        ];

        return view('admin.email.campaigns.show', compact('row', 'template', 'sender', 'segment', 'deliveryStats'));
    }

    public function edit(int $campaign): View
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $templates = DB::table('email_templates')->where('is_active', true)->orderBy('name')->get();
        $groups = DB::table('email_groups')->orderBy('name')->get();
        $segments = DB::table('email_segments')->orderBy('name')->get();
        $senders = DB::table('email_senders_extension')->orderBy('sender_name')->get();

        $assignedGroupIds = json_decode($row->group_ids, true) ?? [];

        return view('admin.email.campaigns.edit', compact('row', 'templates', 'groups', 'segments', 'senders', 'assignedGroupIds'));
    }

    public function update(Request $request, int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'template_id' => ['nullable', 'integer'],
            'sender_id' => ['nullable', 'integer'],
            'group_ids' => ['nullable', 'array'],
            'segment_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'preview_text' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('email_campaigns')->where('id', $campaign)->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'template_id' => $data['template_id'] ?? null,
            'sender_id' => $data['sender_id'] ?? null,
            'group_ids' => json_encode($data['group_ids'] ?? []),
            'segment_id' => $data['segment_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'preview_text' => $data['preview_text'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaign}")->with('status', 'Campaign updated.');
    }

    public function destroy(int $campaign): RedirectResponse
    {
        DB::table('email_campaigns')->where('id', $campaign)->delete();

        return redirect('/email/campaigns')->with('status', 'Campaign deleted.');
    }

    public function launch(int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $recipientCount = $this->resolveRecipientCount($row);

        DB::table('email_campaigns')->where('id', $campaign)->update([
            'status' => 'sending',
            'recipient_count' => $recipientCount,
            'sent_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaign}")->with('status', "Campaign launching to {$recipientCount} recipients.");
    }

    public function pause(int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        DB::table('email_campaigns')->where('id', $campaign)->update([
            'status' => 'paused',
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaign}")->with('status', 'Campaign paused.');
    }

    public function resume(int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        DB::table('email_campaigns')->where('id', $campaign)->update([
            'status' => 'sending',
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaign}")->with('status', 'Campaign resumed.');
    }

    public function cancel(int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        DB::table('email_campaigns')->where('id', $campaign)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$campaign}")->with('status', 'Campaign cancelled.');
    }

    public function sendTest(int $campaign, Request $request): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        // In a real implementation, this would dispatch a job to send the test email
        return redirect("/email/campaigns/{$campaign}")->with('status', "Test email queued for {$data['test_email']}.");
    }

    public function duplicate(int $campaign): RedirectResponse
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $newId = DB::table('email_campaigns')->insertGetId([
            'name' => "{$row->name} (Copy)",
            'subject' => $row->subject,
            'template_id' => $row->template_id,
            'sender_id' => $row->sender_id,
            'group_ids' => $row->group_ids,
            'segment_id' => $row->segment_id,
            'preview_text' => $row->preview_text,
            'status' => 'draft',
            'recipient_count' => 0,
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
            'bounce_count' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/campaigns/{$newId}/edit")->with('status', 'Campaign duplicated.');
    }

    public function recipients(int $campaign): View
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $groupIds = json_decode($row->group_ids, true) ?? [];
        $recipients = collect();

        if (! empty($groupIds)) {
            $recipients = DB::table('email_group_subscriber')
                ->join('email_subscribers', 'email_subscribers.id', '=', 'email_group_subscriber.subscriber_id')
                ->whereIn('email_group_subscriber.group_id', $groupIds)
                ->where('email_subscribers.status', 'active')
                ->select('email_subscribers.*')
                ->distinct()
                ->paginate(50);
        }

        if ($row->segment_id) {
            // For segment-based campaigns, show preview
            $recipients = DB::table('email_subscribers')->where('status', 'active')->paginate(50);
        }

        return view('admin.email.campaigns.show', [
            'row' => $row,
            'recipients' => $recipients,
            'template' => null,
            'sender' => null,
            'segment' => null,
            'deliveryStats' => [],
            'showRecipients' => true,
        ]);
    }

    public function analytics(int $campaign): View
    {
        $row = DB::table('email_campaigns')->find($campaign);
        abort_unless($row, 404);

        $deliveryLogs = DB::table('email_delivery_logs')
            ->where('campaign_id', $campaign)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return view('admin.email.campaigns.show', [
            'row' => $row,
            'deliveryLogs' => $deliveryLogs,
            'template' => null,
            'sender' => null,
            'segment' => null,
            'deliveryStats' => [
                'sent' => $deliveryLogs->get('sent', 0),
                'delivered' => $deliveryLogs->get('delivered', 0),
                'opened' => $deliveryLogs->get('opened', 0),
                'clicked' => $deliveryLogs->get('clicked', 0),
                'bounced' => $deliveryLogs->get('bounced', 0),
                'unsubscribed' => $deliveryLogs->get('unsubscribed', 0),
            ],
            'showAnalytics' => true,
        ]);
    }

    private function resolveRecipientCount($campaign): int
    {
        $groupIds = json_decode($campaign->group_ids, true) ?? [];

        if (! empty($groupIds)) {
            return DB::table('email_group_subscriber')
                ->join('email_subscribers', 'email_subscribers.id', '=', 'email_group_subscriber.subscriber_id')
                ->whereIn('email_group_subscriber.group_id', $groupIds)
                ->where('email_subscribers.status', 'active')
                ->distinct('email_subscribers.id')
                ->count('email_subscribers.id');
        }

        if ($campaign->segment_id) {
            return DB::table('email_subscribers')->where('status', 'active')->count();
        }

        return 0;
    }
}
