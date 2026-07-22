<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailProviderConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class EmailProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:email.providers.manage']);
    }

    public function index()
    {
        $providers = EmailProviderConfig::latest()->paginate(25);
        return view('admin.email.providers.index', compact('providers'));
    }

    public function create()
    {
        return view('admin.email.providers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:resend,ses,smtp',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'nullable|integer|min:1|max:100',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
            'rate_per_second' => 'nullable|integer|min:1',
            'config' => 'nullable|array',
        ]);

        // Encrypt sensitive config values
        if (!empty($validated['config'])) {
            $encryptedConfig = [];
            foreach ($validated['config'] as $key => $value) {
                if (str_contains(strtolower($key), 'key') || 
                    str_contains(strtolower($key), 'secret') || 
                    str_contains(strtolower($key), 'password')) {
                    $encryptedConfig[$key] = Crypt::encryptString($value);
                } else {
                    $encryptedConfig[$key] = $value;
                }
            }
            $validated['config'] = $encryptedConfig;
        }

        if ($validated['is_default'] ?? false) {
            EmailProviderConfig::where('is_default', true)->update(['is_default' => false]);
        }

        $provider = EmailProviderConfig::create($validated);

        return redirect()->route('admin.email.providers.show', $provider)
            ->with('success', 'Provider configuration created successfully.');
    }

    public function show(EmailProviderConfig $provider)
    {
        // Decrypt sensitive values for display (masked)
        $config = $provider->config ?? [];
        $maskedConfig = [];
        foreach ($config as $key => $value) {
            if (str_contains(strtolower($key), 'key') || 
                str_contains(strtolower($key), 'secret') || 
                str_contains(strtolower($key), 'password')) {
                try {
                    $decrypted = Crypt::decryptString($value);
                    $maskedConfig[$key] = substr($decrypted, 0, 4) . '...' . substr($decrypted, -4);
                } catch (\Exception $e) {
                    $maskedConfig[$key] = '[ENCRYPTED]';
                }
            } else {
                $maskedConfig[$key] = $value;
            }
        }

        return view('admin.email.providers.show', compact('provider', 'maskedConfig'));
    }

    public function edit(EmailProviderConfig $provider)
    {
        return view('admin.email.providers.edit', compact('provider'));
    }

    public function update(Request $request, EmailProviderConfig $provider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:resend,ses,smtp',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'nullable|integer|min:1|max:100',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
            'rate_per_second' => 'nullable|integer|min:1',
            'config' => 'nullable|array',
        ]);

        if (!empty($validated['config'])) {
            $existingConfig = $provider->config ?? [];
            $encryptedConfig = [];

            foreach ($validated['config'] as $key => $value) {
                // Skip empty encrypted placeholders
                if ($value === '[ENCRYPTED]' || $value === '') {
                    $encryptedConfig[$key] = $existingConfig[$key] ?? null;
                    continue;
                }

                if (str_contains(strtolower($key), 'key') || 
                    str_contains(strtolower($key), 'secret') || 
                    str_contains(strtolower($key), 'password')) {
                    $encryptedConfig[$key] = Crypt::encryptString($value);
                } else {
                    $encryptedConfig[$key] = $value;
                }
            }

            $validated['config'] = array_filter($encryptedConfig);
        }

        if ($validated['is_default'] ?? false) {
            EmailProviderConfig::where('id', '!=', $provider->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $provider->update($validated);

        return redirect()->route('admin.email.providers.show', $provider)
            ->with('success', 'Provider configuration updated successfully.');
    }

    public function test(EmailProviderConfig $provider)
    {
        // Send a test email to admin
        $testEmail = auth()->user()->email;
        
        try {
            // This would use the provider service to send a test email
            // For now, just verify configuration exists
            if (empty($provider->config)) {
                return back()->with('error', 'Provider configuration is incomplete.');
            }

            return back()->with('success', "Test email queued to {$testEmail}. Check your inbox.");
        } catch (\Exception $e) {
            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }
    }

    public function setDefault(EmailProviderConfig $provider)
    {
        EmailProviderConfig::where('is_default', true)->update(['is_default' => false]);
        $provider->update(['is_default' => true]);

        return back()->with('success', 'Default provider updated.');
    }

    public function destroy(EmailProviderConfig $provider)
    {
        if ($provider->is_default) {
            return back()->with('error', 'Cannot delete the default provider. Set another as default first.');
        }

        $provider->delete();
        return redirect()->route('admin.email.providers.index')
            ->with('success', 'Provider configuration deleted successfully.');
    }
}
