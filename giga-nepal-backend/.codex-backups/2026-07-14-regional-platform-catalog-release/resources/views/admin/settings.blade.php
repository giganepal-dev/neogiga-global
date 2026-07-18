@extends('admin.layout')

@section('title','Settings')
@section('crumb','Admin Console / Settings')
@section('page_actions')
<details class="modal">
    <summary class="btn btn-primary">Create Setting</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Create or Edit Setting</h3><span class="badge b-info">CSRF protected</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/settings/admin-settings">@csrf
            <div class="form-grid">
                <div class="field"><label>Group</label><input class="control" name="group" value="general" required></div>
                <div class="field"><label>Key</label><input class="control mono" name="key" placeholder="commerce.checkout.enabled" required></div>
                <div class="field"><label>Type</label><select class="control" name="type"><option>string</option><option>boolean</option><option>integer</option><option>json</option></select></div>
                <div class="field"><label>Public</label><select class="control" name="is_public"><option value="0">Private</option><option value="1">Public</option></select></div>
            </div>
            <div class="field"><label>Value</label><textarea class="control mono" name="value" placeholder="Setting value"></textarea></div>
            <div class="field"><label>Description</label><textarea class="control" name="description" placeholder="Admin note or operational context"></textarea></div>
            <button class="btn btn-primary" type="submit">Save Setting</button>
        </form>
    </div>
</details>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Admin settings</div><div class="v tnum">{{ number_format($adminSettings->count()) }}</div><div class="s">filtered keys</div></div>
    <div class="kpi"><div class="t">Marketplaces</div><div class="v tnum">{{ number_format($marketplaces->count()) }}</div><div class="s">configured storefronts</div></div>
    <div class="kpi"><div class="t">Roles</div><div class="v tnum">{{ number_format($roles->count()) }}</div><div class="s">permission groups</div></div>
    <div class="kpi"><div class="t">Currencies</div><div class="v tnum">{{ number_format($currencies->count()) }}</div><div class="s">active currencies</div></div>
</div>

<div class="grid dashboard-split">
    <section class="card">
        <div class="card-h"><div><h2>Admin Settings</h2><div class="sub">Create, edit, delete, filter and audit console settings</div></div></div>
        <form class="filters" method="get">
            <select class="control" name="group"><option value="">All groups</option>@foreach($settingGroups as $group)<option value="{{ $group }}" @selected($filters['group']===$group)>{{ $group }}</option>@endforeach</select>
            <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search key or description">
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Group</th><th>Key</th><th>Value</th><th>Type</th><th>Public</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($adminSettings as $setting)
                <tr>
                    <td>{{ $setting->group }}</td>
                    <td class="mono">{{ $setting->key }}</td>
                    <td class="mono">{{ \Illuminate\Support\Str::limit((string) $setting->value, 46) }}</td>
                    <td>{{ $setting->type }}</td>
                    <td><span class="badge {{ $setting->is_public ? 'b-ok':'b-muted' }}">{{ $setting->is_public ? 'public':'private' }}</span></td>
                    <td class="actions">
                        <details class="modal">
                            <summary class="btn btn-ghost icon-btn" title="Edit">Edit</summary>
                            <div class="modal-panel">
                                <div class="modal-h"><h3>Edit {{ $setting->key }}</h3><span class="badge b-info">{{ $setting->group }}</span></div>
                                <form class="modal-b form-stack" method="post" action="/admin/settings/admin-settings">@csrf
                                    <input type="hidden" name="key" value="{{ $setting->key }}">
                                    <div class="form-grid">
                                        <div class="field"><label>Group</label><input class="control" name="group" value="{{ $setting->group }}" required></div>
                                        <div class="field"><label>Type</label><input class="control" name="type" value="{{ $setting->type }}" required></div>
                                    </div>
                                    <div class="field"><label>Value</label><textarea class="control mono" name="value">{{ $setting->value }}</textarea></div>
                                    <div class="field"><label>Description</label><textarea class="control" name="description">{{ $setting->description }}</textarea></div>
                                    <label><input type="checkbox" name="is_public" value="1" @checked($setting->is_public)> Public setting</label>
                                    <button class="btn btn-primary" type="submit">Save Changes</button>
                                </form>
                            </div>
                        </details>
                        <form method="post" action="/admin/settings/admin-settings/{{ $setting->id }}" onsubmit="return confirm('Delete this setting?')">@csrf @method('DELETE')
                            <button class="btn btn-ghost danger icon-btn" type="submit" title="Delete">Del</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No settings found</h3><p>Create the first setting from the action button.</p></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-h"><div><h2>Roles & Permissions</h2><div class="sub">Role matrix foundation</div></div><span class="badge b-warn">editor placeholder</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Role</th><th>Status</th><th>Permissions</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td><strong>{{ $role->display_name ?? $role->name }}</strong><div class="sub">{{ $role->description }}</div></td>
                    <td><span class="badge {{ $role->is_active ? 'b-ok':'b-muted' }}">{{ $role->is_active ? 'active':'inactive' }}</span></td>
                    <td class="mono">{{ \Illuminate\Support\Str::limit((string) $role->permissions, 42) }}</td>
                    <td><button class="btn btn-ghost" disabled>Edit permissions</button></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </section>
</div>

<section class="card stack-gap">
    <div class="card-h"><div><h2>Marketplace Settings</h2><div class="sub">Commerce, currency, checkout, tax, SEO, email, payment and inventory settings</div></div></div>
    <form class="filters" method="get">
        <select class="control" name="marketplace_id"><option value="">All marketplaces</option>@foreach($marketplaces as $m)<option value="{{ $m->id }}" @selected($filters['marketplace_id']==$m->id)>#{{ $m->id }} {{ $m->name ?? $m->slug }}</option>@endforeach</select>
        <select class="control" name="setting_group"><option value="">All setting groups</option>@foreach($marketplaceGroups as $group)<option value="{{ $group }}" @selected($filters['setting_group']===$group)>{{ $group }}</option>@endforeach</select>
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search key">
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="tabs">@foreach(['Commerce','Currency','Checkout','Tax','SEO','Email','Payment','Inventory'] as $tab)<span class="tab">{{ $tab }}</span>@endforeach</div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Marketplace</th><th>Group</th><th>Key</th><th>Value</th><th>Type</th><th>Public</th></tr></thead>
        <tbody>
        @forelse($marketplaceSettings as $setting)
            <tr>
                <td class="tnum">{{ $setting->marketplace_id }}</td>
                <td>{{ $setting->group }}</td>
                <td class="mono">{{ $setting->key }}</td>
                <td class="mono">{{ \Illuminate\Support\Str::limit((string) $setting->value, 54) }}</td>
                <td>{{ $setting->type }}</td>
                <td><span class="badge {{ $setting->is_public ? 'b-ok':'b-muted' }}">{{ $setting->is_public ? 'public':'private' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No marketplace settings</h3><p>Marketplace-specific settings will appear here when configured.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
