<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSenderIdentity;
use App\Models\EmailProviderConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmailSenderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:email.providers.manage']);
    }

    public function index()
    {
        $senders = EmailSenderIdentity::with('creator')->latest()->paginate(25);
        return view('admin.email.senders.index', compact('senders'));
    }

    public function create()
    {
        $providerConfigs = EmailProviderConfig::all();
        return view('admin.email.senders.create', compact('providerConfigs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'provider_config_id' => 'required|exists:email_provider_configs,id',
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
            'verification_status' => 'nullable|in:pending,verified,failed',
            'domain' => 'nullable|string|max:255',
        ]);

        // If default, unset others
        if ($validated['is_default'] ?? false) {
            EmailSenderIdentity::where('is_default', true)->update(['is_default' => false]);
        }

        $sender = EmailSenderIdentity::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.email.senders.show', $sender)
            ->with('success', 'Sender identity created successfully.');
    }

    public function show(EmailSenderIdentity $sender)
    {
        $sender->load('providerConfig');
        return view('admin.email.senders.show', compact('sender'));
    }

    public function edit(EmailSenderIdentity $sender)
    {
        $providerConfigs = EmailProviderConfig::all();
        return view('admin.email.senders.edit', compact('sender', 'providerConfigs'));
    }

    public function update(Request $request, EmailSenderIdentity $sender)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'provider_config_id' => 'required|exists:email_provider_configs,id',
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
            'verification_status' => 'nullable|in:pending,verified,failed',
            'domain' => 'nullable|string|max:255',
        ]);

        if ($validated['is_default'] ?? false) {
            EmailSenderIdentity::where('id', '!=', $sender->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $sender->update($validated);

        return redirect()->route('admin.email.senders.show', $sender)
            ->with('success', 'Sender identity updated successfully.');
    }

    public function verify(EmailSenderIdentity $sender)
    {
        $provider = $sender->providerConfig->provider;
        
        // Trigger verification based on provider
        if ($provider === 'resend') {
            // Resend verification handled via API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.resend.api_key'),
            ])->post('https://api.resend.com/domains/' . $sender->domain . '/verify');
            
            if ($response->successful()) {
                $sender->update(['verification_status' => 'verified', 'is_verified' => true]);
                return back()->with('success', 'Sender verified successfully via Resend.');
            } else {
                return back()->with('error', 'Verification failed: ' . $response->body());
            }
        } elseif ($provider === 'ses') {
            // SES verification requires AWS SDK - mark as pending for manual verification
            $sender->update(['verification_status' => 'pending']);
            return back()->with('info', 'SES verification initiated. Please check AWS SES console to verify email/domain.');
        }

        return back()->with('info', 'Verification method not available for this provider.');
    }

    public function destroy(EmailSenderIdentity $sender)
    {
        if ($sender->is_default) {
            return back()->with('error', 'Cannot delete the default sender identity. Set another as default first.');
        }

        $sender->delete();
        return redirect()->route('admin.email.senders.index')
            ->with('success', 'Sender identity deleted successfully.');
    }

    public function setDefault(EmailSenderIdentity $sender)
    {
        EmailSenderIdentity::where('is_default', true)->update(['is_default' => false]);
        $sender->update(['is_default' => true]);

        return back()->with('success', 'Default sender identity updated.');
    }
}
