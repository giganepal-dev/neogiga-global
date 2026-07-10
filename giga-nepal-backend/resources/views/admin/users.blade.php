@extends('admin.layout')
@section('title','Users & Roles')
@section('crumb','Accounts / Access Control')
@section('page_actions')
<details class="modal"><summary class="btn btn-primary">Create User</summary><div class="modal-panel"><div class="modal-h"><h3>Create Staff User</h3><span class="badge b-info">invite-ready</span></div><form class="modal-b form-stack" method="post" action="/admin/users">@csrf<div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" required></div><div class="field"><label>Email</label><input class="control" type="email" name="email" required></div><div class="field"><label>Role</label><select class="control" name="role_id"><option value="">No role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>@endforeach</select></div><div class="field"><label>Temporary password</label><input class="control" name="temporary_password" type="password" minlength="8"></div></div><div class="form-grid"><div class="field"><label>Country access</label><input class="control" name="country_access" placeholder="Global, Nepal"></div><div class="field"><label>Seller/org access</label><input class="control" name="seller_org" placeholder="Seller ID or organization"></div></div><button class="btn btn-primary" type="submit">Create User</button></form></div></details>
@endsection
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Users</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">accounts</div></div>
    <div class="kpi"><div class="t">Admins</div><div class="v tnum">{{ number_format($stats['admins']) }}</div><div class="s">admin roles</div></div>
    <div class="kpi"><div class="t">Verified</div><div class="v tnum">{{ number_format($stats['verified']) }}</div><div class="s">email verified</div></div>
    <div class="kpi"><div class="t">Login activity</div><div class="v tnum">{{ number_format($stats['recentLogins']) }}</div><div class="s">with login date</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>User Manager</h2><div class="sub">{{ number_format($users->total()) }} filtered accounts</div></div><span class="badge b-info">roles + reset</span></div>
    <form class="filters" method="get"><input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name or email"><select class="control" name="role_id"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($filters['role_id']==$role->id)>{{ $role->display_name ?? $role->name }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Last login</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($users as $u)
            @php $role = $u->role->name ?? 'customer'; $admin = in_array($role,['super_admin','admin']); @endphp
            <tr>
                <td><strong>{{ $u->name }}</strong><div class="sub">#{{ $u->id }}</div></td>
                <td class="mono">{{ $u->email }}</td>
                <td><span class="badge {{ $admin?'b-info':'b-muted' }}">{{ ucfirst(str_replace('_',' ',$role)) }}</span></td>
                <td>@if($u->email_verified_at)<span class="badge b-ok">Verified</span>@else<span class="badge b-muted">Unverified</span>@endif</td>
                <td>{{ optional($u->last_login_at)->diffForHumans() ?? '—' }}</td>
                <td class="actions">
                    <details class="modal"><summary class="btn btn-ghost">Edit</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $u->name }}</h3><span class="badge b-info">access</span></div><form class="modal-b form-stack" method="post" action="/admin/users">@csrf<input type="hidden" name="id" value="{{ $u->id }}"><div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $u->name }}" required></div><div class="field"><label>Email</label><input class="control" name="email" value="{{ $u->email }}" required></div><div class="field"><label>Role</label><select class="control" name="role_id"><option value="">No role</option>@foreach($roles as $roleRow)<option value="{{ $roleRow->id }}" @selected($u->role_id===$roleRow->id)>{{ $roleRow->display_name ?? $roleRow->name }}</option>@endforeach</select></div><div class="field"><label>New password</label><input class="control" name="temporary_password" type="password"></div></div><div class="form-grid"><div class="field"><label>Country access</label><input class="control" name="country_access" placeholder="placeholder"></div><div class="field"><label>Seller/org</label><input class="control" name="seller_org" placeholder="placeholder"></div></div><label><input type="checkbox" name="disable" value="1"> Disable tokens/sessions</label><button class="btn btn-primary" type="submit">Save User</button></form></div></details>
                    <form method="post" action="/admin/users/{{ $u->id }}/send-reset">@csrf<button class="btn btn-ghost" type="submit">Reset</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No users</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($users->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $users->links() }}</div>@endif
</section>

<div class="grid split stack-gap">
    <section class="card"><div class="card-h"><h2>Permission Matrix</h2><span class="badge b-warn">placeholder</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Role</th><th>Admin</th><th>Catalog</th><th>Orders</th><th>Marketing</th></tr></thead><tbody>@foreach($roles as $role)<tr><td>{{ $role->display_name ?? $role->name }}</td><td><span class="badge b-info">view</span></td><td><span class="badge b-info">edit</span></td><td><span class="badge b-muted">assign</span></td><td><span class="badge b-muted">assign</span></td></tr>@endforeach</tbody></table></div></section>
    <section class="card"><div class="card-h"><h2>Audit Trail</h2><span class="badge b-info">latest</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Action</th><th>Model</th><th>User</th><th>When</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr><td>{{ $log->action }}</td><td>{{ $log->model_type }} #{{ $log->model_id }}</td><td>{{ $log->user_id ?: 'system' }}</td><td>{{ $log->created_at }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No audit rows</h3></div></td></tr>@endforelse</tbody></table></div></section>
</div>

@endsection
