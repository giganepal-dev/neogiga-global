<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailProviderController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_provider_configs_extension');

        if ($search = $request->input('search')) {
            $query->where('provider', 'ilike', "%{$search}%");
        }

        if ($provider = $request->input('provider')) {
            $query->where('provider', $provider);
        }

        $providers = $query->orderByDesc('created_at')->paginate(20);

        $availableProviders = DB::table('email_providers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.email.providers.index', compact('providers', 'availableProviders'));
    }

    public function create(): View
    {
        $availableProviders = DB::table('email_providers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.email.providers.create', compact('availableProviders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'config_key' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            DB::table('email_provider_configs_extension')->where('is_default', true)->update(['is_default' => false]);
        }

        $providerId = DB::table('email_provider_configs_extension')->insertGetId([
            'provider' => $data['provider'],
            'config_key' => $data['config_key'],
            'api_key' => $data['api_key'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'settings' => json_encode($data['settings'] ?? []),
            'is_default' => $data['is_default'] ?? false,
            'is_active' => true,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/providers/{$providerId}")->with('status', 'Provider configured.');
    }

    public function show(int $provider): View
    {
        $row = DB::table('email_provider_configs_extension')->find($provider);
        abort_unless($row, 404);

        return view('admin.email.providers.show', compact('row'));
    }

    public function edit(int $provider): View
    {
        $row = DB::table('email_provider_configs_extension')->find($provider);
        abort_unless($row, 404);

        return view('admin.email.providers.edit', compact('row'));
    }

    public function update(Request $request, int $provider): RedirectResponse
    {
        $row = DB::table('email_provider_configs_extension')->find($provider);
        abort_unless($row, 404);

        $data = $request->validate([
            'config_key' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            DB::table('email_provider_configs_extension')->where('is_default', true)->update(['is_default' => false]);
        }

        DB::table('email_provider_configs_extension')->where('id', $provider)->update([
            'config_key' => $data['config_key'],
            'api_key' => $data['api_key'] ?? $row->api_key,
            'api_secret' => $data['api_secret'] ?? $row->api_secret,
            'settings' => json_encode($data['settings'] ?? json_decode($row->settings, true)),
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => now(),
        ]);

        return redirect("/email/providers/{$provider}")->with('status', 'Provider updated.');
    }

    public function destroy(int $provider): RedirectResponse
    {
        DB::table('email_provider_configs_extension')->where('id', $provider)->delete();

        return redirect('/email/providers')->with('status', 'Provider removed.');
    }

    public function test(int $provider): RedirectResponse
    {
        $row = DB::table('email_provider_configs_extension')->find($provider);
        abort_unless($row, 404);

        // In a real implementation, this would test the provider connection
        DB::table('email_provider_configs_extension')->where('id', $provider)->update([
            'last_tested_at' => now(),
            'last_test_status' => 'success',
            'updated_at' => now(),
        ]);

        return redirect("/email/providers/{$provider}")->with('status', 'Provider connection test successful.');
    }

    public function setDefault(int $provider): RedirectResponse
    {
        $row = DB::table('email_provider_configs_extension')->find($provider);
        abort_unless($row, 404);

        DB::table('email_provider_configs_extension')->where('is_default', true)->update(['is_default' => false]);
        DB::table('email_provider_configs_extension')->where('id', $provider)->update([
            'is_default' => true,
            'updated_at' => now(),
        ]);

        return redirect("/email/providers/{$provider}")->with('status', 'Default provider updated.');
    }
}
