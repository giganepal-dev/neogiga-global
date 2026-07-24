@extends('admin.layout')
@section('title', $row->name ?? 'Segment')
@section('crumb', 'Email / Segments / ' . ($row->name ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $row->name }}</h2>
        <p>{{ $row->description ?? 'No description' }}</p>
    </div>
    <div class="page-actions">
        <a href="/email/segments/{{ $row->id }}/preview" class="btn btn-ghost" target="_blank">Preview</a>
        <a href="/email/segments/{{ $row->id }}/edit" class="btn btn-ghost">Edit</a>
        <form method="POST" action="/email/segments/{{ $row->id }}/recalculate" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary">Recalculate</button>
        </form>
        <form method="POST" action="/email/segments/{{ $row->id }}" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger" data-confirm="Delete this segment?">Delete</button>
        </form>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Matched Subscribers</div>
        <div class="v">{{ number_format($row->subscriber_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Type</div>
        <div class="v" style="font-size:1rem">{{ $row->segment_type ?? 'dynamic' }}</div>
    </div>
    <div class="kpi">
        <div class="t">Last Calculated</div>
        <div class="v" style="font-size:1rem">{{ $row->last_calculated_at?->diffForHumans() ?? 'Never' }}</div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Rules</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Field</th>
                    <th>Operator</th>
                    <th>Value</th>
                    <th>Boolean</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $index => $r)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td><span class="badge b-info">{{ $r->field }}</span></td>
                    <td>{{ $r->operator }}</td>
                    <td class="mono">{{ $r->value ?? '—' }}</td>
                    <td>{{ $r->boolean_operator ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty"><p>No rules defined.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Preview (first 20 matches)</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Engagement</th>
                </tr>
            </thead>
            <tbody>
                @forelse($previewSubscribers as $s)
                <tr>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                    <td>
                        @if($s->status === 'active')
                            <span class="badge b-ok">active</span>
                        @else
                            <span class="badge b-muted">{{ $s->status }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $s->engagement_score ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty"><p>No subscribers match these rules.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
