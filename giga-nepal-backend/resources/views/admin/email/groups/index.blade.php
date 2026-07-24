@extends('admin.layout')
@section('title', 'Email Groups')
@section('crumb', 'Email / Groups')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Groups</h2>
        <p>Organize subscribers into groups for targeted campaigns.</p>
    </div>
    <div class="page-actions">
        <a href="/email/groups/create" class="btn btn-primary">New Group</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Groups ({{ $groups->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search groups..." style="width:220px">
            <select class="control" name="type" style="width:140px">
                <option value="">All Types</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Country</th>
                    <th>Subscribers</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $g)
                <tr>
                    <td>
                        <a href="/email/groups/{{ $g->id }}" style="font-weight:600;color:var(--fg)">{{ $g->name }}</a>
                        @if($g->description)<br><small style="color:var(--muted)">{{ Str::limit($g->description, 60) }}</small>@endif
                    </td>
                    <td><span class="badge b-muted">{{ $g->group_type ?? 'manual' }}</span></td>
                    <td>{{ $g->country_code ?? '—' }}</td>
                    <td class="num">{{ number_format($g->subscriber_count ?? 0) }}</td>
                    <td style="white-space:nowrap">{{ $g->created_at?->diffForHumans() ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/email/groups/{{ $g->id }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/email/groups/{{ $g->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty"><p>No groups yet. Create your first group.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $groups->withQueryString()->links() }}
</div>
@endsection
