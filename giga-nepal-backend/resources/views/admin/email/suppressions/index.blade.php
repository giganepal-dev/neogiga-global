@extends('admin.layout')
@section('title', 'Email Suppressions')
@section('crumb', 'Email / Suppressions')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Suppressions</h2>
        <p>Manage the suppression list to prevent sending to certain addresses.</p>
    </div>
    <div class="page-actions">
        <a href="/email/suppressions/export" class="btn btn-ghost">Export CSV</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Total Suppressed</div>
        <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Global</div>
        <div class="v">{{ number_format($stats['global']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Permanent</div>
        <div class="v">{{ number_format($stats['permanent']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Bounces</div>
        <div class="v">{{ number_format($stats['bounces']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Complaints</div>
        <div class="v">{{ number_format($stats['complaints']) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Suppression List</h2>
        <form method="POST" action="/email/suppressions" style="display:flex;gap:8px;align-items:center">
            @csrf
            <input class="control" name="email" type="email" placeholder="Add email to suppress..." required style="width:260px">
            <button type="submit" class="btn btn-primary">Suppress</button>
        </form>
    </div>
    <form method="POST" action="/email/suppressions/bulk-remove">
        @csrf
        <div class="scroll-x">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" data-check-all></th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Source</th>
                        <th>Global</th>
                        <th>Permanent</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppressions as $s)
                    <tr>
                        <td><input type="checkbox" name="suppression_ids[]" value="{{ $s->id }}" data-check></td>
                        <td class="mono">{{ $s->email }}</td>
                        <td><span class="badge b-muted">{{ $s->reason ?? '—' }}</span></td>
                        <td>{{ $s->source ?? '—' }}</td>
                        <td>{{ $s->is_global ? 'Yes' : 'No' }}</td>
                        <td>{{ $s->is_permanent ? 'Yes' : 'No' }}</td>
                        <td style="white-space:nowrap">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <form method="POST" action="/email/suppressions/{{ $s->id }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm danger" data-confirm="Remove suppression?">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty"><p>No suppressed emails.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:10px 16px;border-top:1px solid var(--line);display:flex;gap:8px;align-items:center">
            <button type="submit" class="btn btn-ghost danger" data-confirm="Remove selected suppressions?">Bulk Remove Selected</button>
        </div>
    </form>
    {{ $suppressions->links() }}
</div>
@endsection
