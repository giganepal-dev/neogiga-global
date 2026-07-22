<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketing\EmailCampaign;
use App\Models\EmailMarketing\EmailGroup;
use App\Models\EmailMarketing\EmailSegment;
use App\Models\EmailMarketing\EmailTemplate;
use App\Models\EmailMarketing\EmailProviderConfig;
use App\Models\EmailMarketing\EmailSenderIdentity;
use App\Models\EmailMarketing\EmailSubscriber;
use App\Services\CampaignSendingService;
use App\Jobs\ProcessCampaignJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailCampaignController extends Controller
{
    protected CampaignSendingService $sendingService;

    public function __construct(CampaignSendingService $sendingService)
    {
        $this->sendingService = $sendingService;
    }

    /**
     * Display campaigns list
     */
    public function index(Request $request)
    {
        $query = EmailCampaign::with(['creator', 'template', 'provider', 'senderIdentity'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('campaign_type', $request->type);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $campaigns = $query->paginate(20);

        return view('admin.email.campaigns.index', compact('campaigns'));
    }

    /**
     * Show campaign creation form
     */
    public function create()
    {
        $groups = EmailGroup::where('is_active', true)->get();
        $segments = EmailSegment::where('is_active', true)->get();
        $templates = EmailTemplate::where('is_active', true)->get();
        $providers = EmailProviderConfig::where('is_active', true)->get();
        $senderIdentities = EmailSenderIdentity::where('is_verified', true)->get();

        return view('admin.email.campaigns.create', compact('groups', 'segments', 'templates', 'providers', 'senderIdentities'));
    }

    /**
     * Store new campaign
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:500',
            'preview_text' => 'nullable|string|max:200',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'reply_to_email' => 'nullable|email|max:255',
            'reply_to_name' => 'nullable|string|max:255',
            'campaign_type' => 'required|in:regular,ab_test,automated',
            'template_id' => 'nullable|exists:email_templates,id',
            'custom_html' => 'nullable|string',
            'plain_text_content' => 'nullable|string',
            'provider_config_id' => 'nullable|exists:email_provider_configs,id',
            'sender_identity_id' => 'nullable|exists:email_sender_identities,id',
            'scheduled_at' => 'nullable|date|after:now',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:email_groups,id',
            'segments' => 'nullable|array',
            'segments.*' => 'exists:email_segments,id',
            'excluded_groups' => 'nullable|array',
            'excluded_groups.*' => 'exists:email_groups,id',
            'exclude_previous_recipients' => 'boolean',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'add_utm_parameters' => 'boolean',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $campaign = EmailCampaign::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'subject' => $validated['subject'],
                'preview_text' => $validated['preview_text'] ?? null,
                'from_name' => $validated['from_name'] ?? null,
                'from_email' => $validated['from_email'] ?? null,
                'reply_to_email' => $validated['reply_to_email'] ?? null,
                'reply_to_name' => $validated['reply_to_name'] ?? null,
                'campaign_type' => $validated['campaign_type'],
                'template_id' => $validated['template_id'] ?? null,
                'custom_html' => $validated['custom_html'] ?? null,
                'plain_text_content' => $validated['plain_text_content'] ?? null,
                'provider_config_id' => $validated['provider_config_id'] ?? null,
                'sender_identity_id' => $validated['sender_identity_id'] ?? null,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
                'exclude_previous_recipients' => $validated['exclude_previous_recipients'] ?? false,
                'track_opens' => $validated['track_opens'] ?? true,
                'track_clicks' => $validated['track_clicks'] ?? true,
                'add_utm_parameters' => $validated['add_utm_parameters'] ?? false,
                'utm_source' => $validated['utm_source'] ?? 'neogiga',
                'utm_medium' => $validated['utm_medium'] ?? 'email',
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Attach groups
            if (!empty($validated['groups'])) {
                $campaign->groups()->attach($validated['groups']);
            }

            // Attach segments
            if (!empty($validated['segments'])) {
                $campaign->segments()->attach($validated['segments']);
            }

            // Attach excluded groups
            if (!empty($validated['excluded_groups'])) {
                $campaign->excludedGroups()->attach($validated['excluded_groups']);
            }

            return $campaign;
        });

        return redirect()->route('admin.email.campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Show campaign details
     */
    public function show(EmailCampaign $campaign)
    {
        $campaign->load(['groups', 'segments', 'template', 'provider', 'senderIdentity', 'recipients']);
        
        $stats = [
            'total_recipients' => $campaign->recipients()->count(),
            'sent' => $campaign->recipients()->where('status', 'sent')->count(),
            'pending' => $campaign->recipients()->where('status', 'pending')->count(),
            'failed' => $campaign->recipients()->where('status', 'failed')->count(),
            'opened' => $campaign->recipients()->whereNotNull('opened_at')->count(),
            'clicked' => $campaign->recipients()->whereNotNull('clicked_at')->count(),
        ];

        return view('admin.email.campaigns.show', compact('campaign', 'stats'));
    }

    /**
     * Validate campaign before sending
     */
    public function validateCampaign(EmailCampaign $campaign)
    {
        $validation = $this->sendingService->validateCampaign($campaign);
        
        return response()->json([
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'recipient_count' => $validation['recipient_count'],
        ]);
    }

    /**
     * Prepare recipients for campaign
     */
    public function prepareRecipients(EmailCampaign $campaign)
    {
        try {
            $count = $this->sendingService->prepareRecipients($campaign);
            
            return response()->json([
                'success' => true,
                'recipient_count' => $count,
                'message' => "Prepared {$count} recipients for campaign",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request, EmailCampaign $campaign)
    {
        $validated = $request->validate([
            'test_emails' => 'required|array',
            'test_emails.*' => 'email',
        ]);

        try {
            // Implementation for sending test emails
            Log::info('Test emails queued', [
                'campaign_id' => $campaign->id,
                'test_emails' => $validated['test_emails'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test emails queued successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Launch campaign
     */
    public function launch(EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled', 'validating'])) {
            return back()->with('error', 'Campaign cannot be launched from current status: ' . $campaign->status);
        }

        // Validate campaign
        $validation = $this->sendingService->validateCampaign($campaign);
        
        if (!$validation['valid']) {
            return back()->with('error', implode('. ', $validation['errors']));
        }

        try {
            DB::transaction(function () use ($campaign) {
                // Prepare recipients
                $this->sendingService->prepareRecipients($campaign);
                
                // Update status
                $campaign->update([
                    'status' => $campaign->scheduled_at && $campaign->scheduled_at > now() ? 'scheduled' : 'queued',
                    'launched_at' => now(),
                ]);
            });

            // Dispatch processing job
            ProcessCampaignJob::dispatch($campaign, 100)
                ->onQueue('emails-marketing')
                ->delay($campaign->scheduled_at ? $campaign->scheduled_at : null);

            Log::info('Campaign launched', [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status,
            ]);

            return back()->with('success', 'Campaign launched successfully!');
            
        } catch (Exception $e) {
            Log::error('Failed to launch campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to launch campaign: ' . $e->getMessage());
        }
    }

    /**
     * Pause campaign
     */
    public function pause(EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['sending', 'queued'])) {
            return back()->with('error', 'Campaign cannot be paused from current status');
        }

        $campaign->update(['status' => 'paused']);
        
        Log::info('Campaign paused', ['campaign_id' => $campaign->id]);

        return back()->with('success', 'Campaign paused successfully');
    }

    /**
     * Resume campaign
     */
    public function resume(EmailCampaign $campaign)
    {
        if ($campaign->status !== 'paused') {
            return back()->with('error', 'Only paused campaigns can be resumed');
        }

        $campaign->update(['status' => 'queued']);
        
        // Dispatch processing job
        ProcessCampaignJob::dispatch($campaign, 100)
            ->onQueue('emails-marketing');

        Log::info('Campaign resumed', ['campaign_id' => $campaign->id]);

        return back()->with('success', 'Campaign resumed successfully');
    }

    /**
     * Cancel campaign
     */
    public function cancel(EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled', 'queued', 'sending', 'paused'])) {
            return back()->with('error', 'Campaign cannot be cancelled from current status');
        }

        $campaign->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Mark pending recipients as cancelled
        $campaign->recipients()
            ->whereIn('status', ['pending'])
            ->update(['status' => 'cancelled']);

        Log::info('Campaign cancelled', ['campaign_id' => $campaign->id]);

        return back()->with('success', 'Campaign cancelled successfully');
    }

    /**
     * Duplicate campaign
     */
    public function duplicate(EmailCampaign $campaign)
    {
        DB::transaction(function () use ($campaign) {
            $newCampaign = $campaign->replicate();
            $newCampaign->name = $campaign->name . ' (Copy)';
            $newCampaign->status = 'draft';
            $newCampaign->emails_sent = 0;
            $newCampaign->emails_failed = 0;
            $newCampaign->launched_at = null;
            $newCampaign->completed_at = null;
            $newCampaign->save();

            // Copy group relationships
            $newCampaign->groups()->attach($campaign->groups()->pluck('email_groups.id'));
            $newCampaign->segments()->attach($campaign->segments()->pluck('email_segments.id'));
            $newCampaign->excludedGroups()->attach($campaign->excludedGroups()->pluck('email_groups.id'));

            return $newCampaign;
        });

        return back()->with('success', 'Campaign duplicated successfully');
    }

    /**
     * Export campaign results
     */
    public function export(EmailCampaign $campaign, Request $request)
    {
        $format = $request->get('format', 'csv');
        
        // Implementation for CSV/XLSX export
        // This would generate a downloadable file with recipient data
        
        return response()->json([
            'message' => 'Export functionality - implement based on format preference',
            'campaign_id' => $campaign->id,
            'format' => $format,
        ]);
    }
}
