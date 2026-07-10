@extends('admin.layout')
@section('title','Distributors')
@section('crumb','Network operations')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Distributors</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v tnum">{{ number_format($stats['active']) }}</div><div class="s">approved network</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">needs review</div></div>
    <div class="kpi"><div class="t">Suspended</div><div class="v tnum">{{ number_format($stats['suspended']) }}</div><div class="s">restricted</div></div>
    <div class="kpi"><div class="t">Territories</div><div class="v tnum">{{ number_format($stats['territories']) }}</div><div class="s">assigned</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h">
        <div>
            <h2>Distributor Network</h2>
            <div class="sub">{{ number_format($distributors->total()) }} distributor accounts</div>
        </div>
        <span class="badge b-info">API-backed</span>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr><th>Distributor</th><th>Contact</th><th>Type</th><th>Status</th><th>Joined</th></tr>
            </thead>
            <tbody>
            @forelse ($distributors as $d)
                @php
                    $status = $d->status ?? 'pending';
                    $badge = match ($status) {
                        'active', 'approved' => 'b-ok',
                        'pending', 'review' => 'b-warn',
                        'suspended', 'rejected' => 'b-muted',
                        default => 'b-info',
                    };
                @endphp
                <tr>
                    <td>
                        <strong>{{ $d->business_name ?: ($d->name ?: ('Distributor #'.$d->id)) }}</strong>
                        <div class="sub mono">{{ $d->slug ?? 'no-slug' }}</div>
                    </td>
                    <td>
                        {{ $d->email ?: ($d->user_email ?? '—') }}
                        <div class="sub">{{ $d->phone ?: ($d->user_name ?? '—') }}</div>
                    </td>
                    <td>{{ ucfirst((string) ($d->type ?? 'standard')) }}</td>
                    <td><span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span></td>
                    <td class="sub">{{ $d->created_at ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h18M6 7v10a2 2 0 002 2h8a2 2 0 002-2V7"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        <h3>No distributors yet</h3>
                        <p>Distributor registrations appear here after onboarding through the public distributor flow or API.</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($distributors->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $distributors->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-h">
        <div><h2>Recent Territory Assignments</h2><div class="sub">Latest distributor territory rows</div></div>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Distributor</th><th>Territory</th><th>Country</th><th>Region</th><th>City</th><th>Mode</th></tr></thead>
            <tbody>
            @forelse ($territories as $t)
                <tr>
                    <td><strong>{{ $t->distributor_name ?? ('Distributor #'.$t->distributor_id) }}</strong></td>
                    <td>{{ $t->territory_name ?? '—' }}</td>
                    <td class="mono">{{ $t->country_id ?? '—' }}</td>
                    <td class="mono">{{ $t->region_id ?? '—' }}</td>
                    <td class="mono">{{ $t->city_id ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $t->exclusive ? 'b-warn' : 'b-muted' }}">{{ $t->exclusive ? 'Exclusive' : 'Shared' }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No territories assigned</h3></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="note">Operational actions use the protected distributor admin API endpoints under <span class="mono">/api/v1/admin/distributors</span>. This page exposes the live network state without changing records.</div>

@endsection
