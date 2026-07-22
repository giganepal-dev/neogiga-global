<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailGroup;
use App\Models\EmailSubscriber;
use App\Models\Region;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:email.groups.manage']);
    }

    public function index(Request $request)
    {
        $query = EmailGroup::withCount('subscribers');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('country_code')) {
            $query->where('country_code', $request->country_code);
        }
        
        $groups = $query->latest()->paginate(25);
        $countries = Country::all();
        
        return view('admin.email.groups.index', compact('groups', 'countries'));
    }

    public function create()
    {
        $regions = Region::all();
        $countries = Country::all();
        $senderIdentities = \App\Models\EmailSenderIdentity::all();
        $providerConfigs = \App\Models\EmailProviderConfig::all();
        
        return view('admin.email.groups.create', compact('regions', 'countries', 'senderIdentities', 'providerConfigs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:email_groups,name',
            'description' => 'nullable|string',
            'country_code' => 'nullable|size:2',
            'region_id' => 'nullable|exists:regions,id',
            'is_active' => 'boolean',
            'default_language' => 'nullable|string|max:10',
            'default_currency' => 'nullable|string|max:3',
            'sender_identity_id' => 'nullable|exists:email_sender_identities,id',
            'provider_config_id' => 'nullable|exists:email_provider_configs,id',
            'physical_address' => 'nullable|string',
            'unsubscribe_footer' => 'nullable|string',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
        ]);

        $group = EmailGroup::create($validated);

        return redirect()->route('admin.email.groups.show', $group)
            ->with('success', 'Group created successfully.');
    }

    public function show(EmailGroup $group)
    {
        $group->load(['subscribers' => fn($q) => $q->latest()->limit(50), 'region', 'senderIdentity', 'providerConfig']);
        $stats = [
            'total_subscribers' => $group->subscribers()->count(),
            'subscribed' => $group->subscribers()->where('status', 'subscribed')->count(),
            'unsubscribed' => $group->subscribers()->where('status', 'unsubscribed')->count(),
            'suppressed' => $group->subscribers()->whereIn('status', ['bounced', 'complained', 'suppressed'])->count(),
        ];
        
        return view('admin.email.groups.show', compact('group', 'stats'));
    }

    public function edit(EmailGroup $group)
    {
        $regions = Region::all();
        $countries = Country::all();
        $senderIdentities = \App\Models\EmailSenderIdentity::all();
        $providerConfigs = \App\Models\EmailProviderConfig::all();
        
        return view('admin.email.groups.edit', compact('group', 'regions', 'countries', 'senderIdentities', 'providerConfigs'));
    }

    public function update(Request $request, EmailGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:email_groups,name,'.$group->id,
            'description' => 'nullable|string',
            'country_code' => 'nullable|size:2',
            'region_id' => 'nullable|exists:regions,id',
            'is_active' => 'boolean',
            'default_language' => 'nullable|string|max:10',
            'default_currency' => 'nullable|string|max:3',
            'sender_identity_id' => 'nullable|exists:email_sender_identities,id',
            'provider_config_id' => 'nullable|exists:email_provider_configs,id',
            'physical_address' => 'nullable|string',
            'unsubscribe_footer' => 'nullable|string',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
        ]);

        $group->update($validated);

        return redirect()->route('admin.email.groups.show', $group)
            ->with('success', 'Group updated successfully.');
    }

    public function destroy(EmailGroup $group)
    {
        // Check if group is used in any active campaigns
        $usedInCampaigns = DB::table('email_campaign_groups')
            ->where('group_id', $group->id)
            ->join('email_campaigns', 'email_campaign_groups.campaign_id', '=', 'email_campaigns.id')
            ->where('email_campaigns.status', '!=', 'cancelled')
            ->exists();

        if ($usedInCampaigns) {
            return back()->with('error', 'Cannot delete group used in active campaigns.');
        }

        // Detach all subscribers
        $group->subscribers()->detach();
        $group->delete();

        return redirect()->route('admin.email.groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    public function addSubscribers(Request $request, EmailGroup $group)
    {
        $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'exists:email_subscribers,id',
            'is_primary' => 'boolean',
        ]);

        $subscribers = $request->subscriber_ids;
        $isPrimary = $request->boolean('is_primary', false);

        foreach ($subscribers as $subscriberId) {
            $group->subscribers()->syncWithoutUpdating([$subscriberId => [
                'assignment_source' => 'manual',
                'is_primary' => $isPrimary,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]]);
        }

        return back()->with('success', count($subscribers) . ' subscribers added to group.');
    }

    public function removeSubscribers(Request $request, EmailGroup $group)
    {
        $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'exists:email_subscribers,id',
        ]);

        $group->subscribers()->detach($request->subscriber_ids);

        return back()->with('success', 'Subscribers removed from group.');
    }

    public function export(EmailGroup $group)
    {
        $subscribers = $group->subscribers()
            ->select('email', 'first_name', 'last_name', 'company_name', 'country_code', 'status', 'subscribed_at')
            ->get();

        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(['Email', 'First Name', 'Last Name', 'Company', 'Country', 'Status', 'Subscribed At']);
        $csv->insertAll($subscribers->toArray());

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"group_{$group->id}_subscribers.csv\"",
        ]);
    }
}
