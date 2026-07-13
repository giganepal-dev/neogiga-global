@extends('admin.layout')

@section('title', 'Users & Roles')
@section('crumb', 'Accounts / Access Control')

@section('page_actions')
    <details class="modal">
        <summary class="btn btn-primary">Create User</summary>
        <div class="modal-panel">
            <div class="modal-h"><h3>Create Staff User</h3><span class="badge b-info">direct account</span></div>
            <form class="modal-b form-stack" method="post" action="/admin/users">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Name</label><input class="control" name="name" required></div>
                    <div class="field"><label>Email</label><input class="control" type="email" name="email" required></div>
                    <div class="field"><label>Role</label><select class="control" name="role_id"><option value="">No role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Temporary password</label><input class="control" name="temporary_password" type="password" minlength="8"></div>
                </div>
                <button class="btn btn-primary" type="submit">Create User</button>
            </form>
        </div>
    </details>
    <details class="modal">
        <summary class="btn btn-ghost">Invite User</summary>
        <div class="modal-panel">
            <div class="modal-h"><h3>Invite User</h3><span class="badge b-warn">logged only</span></div>
            <form class="modal-b form-stack" method="post" action="/admin/users/invitations">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Name</label><input class="control" name="name"></div>
                    <div class="field"><label>Email</label><input class="control" type="email" name="email" required></div>
                    <div class="field"><label>Role</label><select class="control" name="role_id"><option value="">No role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Expires in days</label><input class="control" type="number" name="expires_days" value="7" min="1" max="90"></div>
                </div>
                <button class="btn btn-primary" type="submit">Log Invitation</button>
            </form>
        </div>
    </details>
    <details class="modal">
        <summary class="btn btn-ghost">Add Permission</summary>
        <div class="modal-panel">
            <div class="modal-h"><h3>Add Permission</h3></div>
            <form class="modal-b form-stack" method="post" action="/admin/users/permissions">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Key</label><input class="control mono" name="key" placeholder="catalog.products.edit" required></div>
                    <div class="field"><label>Name</label><input class="control" name="name" required></div>
                    <div class="field"><label>Group</label><input class="control" name="group" value="admin" required></div>
                </div>
                <div class="field"><label>Description</label><textarea class="control" name="description"></textarea></div>
                <button class="btn btn-primary" type="submit">Save Permission</button>
            </form>
        </div>
    </details>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Users</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">accounts</div></div>
    <div class="kpi"><div class="t">Admins</div><div class="v tnum">{{ number_format($stats['admins']) }}</div><div class="s">admin roles</div></div>
    <div class="kpi"><div class="t">Verified</div><div class="v tnum">{{ number_format($stats['verified']) }}</div><div class="s">email verified</div></div>
    <div class="kpi"><div class="t">Invitations</div><div class="v tnum">{{ number_format($stats['pendingInvites']) }}</div><div class="s">pending</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>User Manager</h2><div class="sub">{{ number_format($users->total()) }} filtered accounts</div></div><span class="badge b-info">roles + access</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name or email">
        <select class="control" name="role_id"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($filters['role_id'] == $role->id)>{{ $role->display_name ?? $role->name }}</option>@endforeach</select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Access</th><th>Last login</th><th>Actions</th></tr></thead><tbody>
        @forelse($users as $user)
            @php
                $roleName = $user->role?->name ?? 'customer';
                $isAdmin = in_array($roleName, ['super_admin', 'admin'], true);
                $userCountries = $countryAccess[$user->id] ?? collect();
                $userSellers = $sellerAccess[$user->id] ?? collect();
            @endphp
            <tr>
                <td><strong>{{ $user->name }}</strong><div class="sub">#{{ $user->id }}</div></td>
                <td class="mono">{{ $user->email }}<div class="sub">{{ $user->email_verified_at ? 'verified' : 'unverified' }}</div></td>
                <td><span class="badge {{ $isAdmin ? 'b-info' : 'b-muted' }}">{{ ucfirst(str_replace('_', ' ', $roleName)) }}</span></td>
                <td><div class="sub">Countries: {{ $userCountries->pluck('name')->join(', ') ?: 'none' }}</div><div class="sub">Sellers: {{ $userSellers->pluck('name')->join(', ') ?: 'none' }}</div></td>
                <td>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                <td class="actions">
                    <details class="modal"><summary class="btn btn-ghost">Edit</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $user->name }}</h3><span class="badge b-info">profile</span></div><form class="modal-b form-stack" method="post" action="/admin/users">@csrf<input type="hidden" name="id" value="{{ $user->id }}"><div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $user->name }}" required></div><div class="field"><label>Email</label><input class="control" name="email" value="{{ $user->email }}" required></div><div class="field"><label>Role</label><select class="control" name="role_id"><option value="">No role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->display_name ?? $role->name }}</option>@endforeach</select></div><div class="field"><label>New password</label><input class="control" name="temporary_password" type="password" minlength="8"></div></div><label><input type="checkbox" name="disable" value="1"> Disable tokens and sessions</label><button class="btn btn-primary" type="submit">Save User</button></form></div></details>
                    <details class="modal"><summary class="btn btn-ghost">Access</summary><div class="modal-panel"><div class="modal-h"><h3>Assign Access</h3></div><div class="modal-b form-stack"><form class="form-grid" method="post" action="/admin/users/{{ $user->id }}/country-access">@csrf<select class="control" name="country_id" required><option value="">Country</option>@foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach</select><button class="btn" type="submit">Add Country</button></form><form class="form-grid" method="post" action="/admin/users/{{ $user->id }}/seller-access">@csrf<select class="control" name="vendor_id" required><option value="">Seller</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select><select class="control" name="access_level"><option>manager</option><option>support</option><option>viewer</option></select><button class="btn" type="submit">Add Seller</button></form></div></div></details>
                    <form method="post" action="/admin/users/{{ $user->id }}/send-reset">@csrf<button class="btn btn-ghost" type="submit">Reset</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No users</h3><p>Create an account or log an invitation to begin access setup.</p></div></td></tr>
        @endforelse
    </tbody></table></div>
    @if($users->hasPages())<div class="pagination-wrap">{{ $users->links() }}</div>@endif
</section>

<section class="card stack-gap">
    <div class="card-h"><h2>Permission Matrix</h2><span class="badge b-info">role toggles</span></div>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Permission</th>@foreach($roles as $role)<th>{{ $role->display_name ?? $role->name }}</th>@endforeach</tr></thead><tbody>
        @forelse($permissions as $group => $permissionRows)
            <tr><td colspan="{{ $roles->count() + 1 }}"><strong>{{ $group }}</strong></td></tr>
            @foreach($permissionRows as $permission)
                <tr><td><strong>{{ $permission->name }}</strong><div class="sub mono">{{ $permission->key }}</div></td>@foreach($roles as $role)@php($enabled = ($rolePermissions[$role->id] ?? collect())->pluck('key')->contains($permission->key))<td><form method="post" action="/admin/users/roles/{{ $role->id }}/permissions/{{ $permission->id }}">@csrf<button class="btn btn-ghost" type="submit">{{ $enabled ? 'On' : 'Off' }}</button></form></td>@endforeach</tr>
            @endforeach
        @empty
            <tr><td colspan="{{ $roles->count() + 1 }}"><div class="empty"><h3>No permissions</h3><p>Add permissions through the protected access controls.</p></div></td></tr>
        @endforelse
    </tbody></table></div>
</section>

<div class="grid split stack-gap">
    <section class="card"><div class="card-h"><h2>Invitations</h2><span class="badge b-warn">email logged only</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Expires</th></tr></thead><tbody>@forelse($invitations as $invitation)<tr><td class="mono">{{ $invitation->email }}</td><td>{{ $invitation->name }}</td><td><span class="badge b-muted">{{ $invitation->status }}</span></td><td>{{ $invitation->expires_at }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No invitations</h3></div></td></tr>@endforelse</tbody></table></div></section>
    <section class="card"><div class="card-h"><h2>Audit Trail</h2><span class="badge b-info">latest</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Action</th><th>Model</th><th>User</th><th>When</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr><td>{{ $log->action }}</td><td>{{ $log->model_type }} #{{ $log->model_id }}</td><td>{{ $log->user_id ?: 'system' }}</td><td>{{ $log->created_at }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No audit rows</h3></div></td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
