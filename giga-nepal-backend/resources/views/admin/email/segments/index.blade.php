@extends('admin.layout')
@section('title', 'Email Segments')
@section('crumb', 'Email / Segments')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Segments</h2>
        <p>Define dynamic subscriber segments based on rules.</p>
    </div>
    <div class="page-actions">
        <a href="/email/segments/create" class="btn btn-primary">New Segment</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Segments ({{ $segments->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search segments..." style="width:220px">
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
                    <th>Subscribers</th>
                    <th>Last Calculated</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($segments as $seg)
                <tr>
                    <td>
                        <a href="/email/segments/{{ $seg->id }}" style="font-weight:600;color:var(--fg)">{{ $seg->name }}</a>
                        @if($seg->description)<br><small style="color:var(--muted)">{{ Str::limit($seg->description, 60) }}</small>@endif
                    </td>
                    <td><span class="badge b-muted">{{ $seg->segment_type ?? 'dynamic' }}</span></td>
                    <td class="num">{{ number_format($seg->subscriber_count ?? 0) }}</td>
                    <td style="white-space:nowrap">{{ $seg->last_calculated_at?->diffForHumans() ?? 'Never' }}</td>
                    <td style="white-space:nowrap">{{ $seg->created_at?->diffForHumans() ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/email/segments/{{ $seg->id }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/email/segments/{{ $seg->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                        <form method="POST" action="/email/segments/{{ $seg->id }}/recalculate" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Recalculate</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty"><p>No segments yet.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $segments->withQueryString()->links() }}
</div>
@endsection
