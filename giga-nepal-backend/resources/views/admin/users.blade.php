@extends('admin.layout')
@section('title','Users & Roles')
@section('crumb','Accounts and access')
@section('content')

<div class="card">
    <div class="card-h"><div><h2>Users</h2><div class="sub">{{ number_format($users->total()) }} accounts</div></div></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th></tr></thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td class="mono">{{ $u->email }}</td>
                    <td>
                        @php $role = $u->role->name ?? 'customer'; $admin = in_array($role,['super_admin','admin']); @endphp
                        <span class="badge {{ $admin?'b-info':'b-muted' }}">{{ ucfirst(str_replace('_',' ',$role)) }}</span>
                    </td>
                    <td>{{ optional($u->last_login_at)->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No users</h3></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $users->links() }}</div>
    @endif
</div>

@endsection
