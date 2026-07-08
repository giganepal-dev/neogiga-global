@extends('admin.layout')

@section('title','Settings')
@section('crumb','Admin Console / Settings')

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Admin settings</div><div class="v tnum">{{ number_format($adminSettings->count()) }}</div><div class="s">Console-level keys</div></div>
    <div class="kpi"><div class="t">Marketplaces</div><div class="v tnum">{{ number_format($marketplaces->count()) }}</div><div class="s">Configured storefronts</div></div>
    <div class="kpi"><div class="t">Countries</div><div class="v tnum">{{ number_format($countries->count()) }}</div><div class="s">Active locations</div></div>
    <div class="kpi"><div class="t">Currencies</div><div class="v tnum">{{ number_format($currencies->count()) }}</div><div class="s">Active currencies</div></div>
</div>

<div class="note">Settings writes are exposed through protected API endpoints so changes can be audited and validated. Public pages should not expose raw source links or internal configuration.</div>

<div class="grid dashboard-split">
    <section class="card">
        <div class="card-h"><h2>Admin Settings</h2><span class="sub">API: /api/v1/admin/console/settings</span></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Group</th><th>Key</th><th>Type</th><th>Public</th><th>Description</th></tr></thead>
                <tbody>
                @forelse($adminSettings as $setting)
                    <tr>
                        <td>{{ $setting->group }}</td>
                        <td class="mono">{{ $setting->key }}</td>
                        <td>{{ $setting->type }}</td>
                        <td><span class="badge {{ $setting->is_public ? 'b-ok':'b-muted' }}">{{ $setting->is_public ? 'yes':'no' }}</span></td>
                        <td>{{ $setting->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><h3>No admin settings yet</h3><p>Create keys through the protected console settings API.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Roles</h2><span class="sub">{{ number_format($roles->count()) }} roles</span></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Name</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($roles as $role)
                    <tr><td>{{ $role->display_name ?? $role->name }}</td><td><span class="badge {{ $role->is_active ? 'b-ok':'b-muted' }}">{{ $role->is_active ? 'active':'inactive' }}</span></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card" style="margin-top:14px">
    <div class="card-h"><h2>Marketplace Settings</h2><span class="sub">Existing configuration table</span></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Marketplace</th><th>Group</th><th>Key</th><th>Type</th><th>Public</th></tr></thead>
            <tbody>
            @forelse($marketplaceSettings as $setting)
                <tr>
                    <td class="tnum">{{ $setting->marketplace_id }}</td>
                    <td>{{ $setting->group }}</td>
                    <td class="mono">{{ $setting->key }}</td>
                    <td>{{ $setting->type }}</td>
                    <td><span class="badge {{ $setting->is_public ? 'b-ok':'b-muted' }}">{{ $setting->is_public ? 'yes':'no' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty"><h3>No marketplace settings</h3><p>Marketplace-specific settings will appear here when configured.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
