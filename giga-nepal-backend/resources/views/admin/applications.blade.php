@extends('admin.layout')
@section('title','Seller & Distributor Applications')
@section('crumb','Onboarding pipeline')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Seller applications</div><div class="v tnum">{{ number_format($stats['sellerTotal']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Seller pending</div><div class="v tnum">{{ number_format($stats['sellerPending']) }}</div><div class="s">to review</div></div>
    <div class="kpi"><div class="t">Distributor applications</div><div class="v tnum">{{ number_format($stats['distributorTotal']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Distributor pending</div><div class="v tnum">{{ number_format($stats['distributorPending']) }}</div><div class="s">to review</div></div>
    <div class="kpi"><div class="t">AI sessions</div><div class="v tnum">{{ number_format($stats['aiSessions']) }}</div><div class="s">commerce AI</div></div>
</div>

@php
    $statusBadge = fn($s) => match($s) {
        'approved_for_onboarding' => 'b-ok',
        'rejected', 'archived' => 'b-muted',
        'contacted' => 'b-info',
        default => 'b-muted',
    };
    $statuses = ['pending','contacted','approved_for_onboarding','rejected','archived'];
@endphp

@foreach ([['Seller Applications', $sellerApps, 'seller'], ['Distributor Applications', $distributorApps, 'distributor']] as [$heading, $rows, $type])
<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>{{ $heading }}</h2><span class="sub">Latest 20 · converts run via the onboarding API</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Business</th><th>Contact</th><th>Status</th><th>Applied</th><th>Set status</th></tr></thead>
        <tbody>
        @forelse ($rows as $a)
            <tr>
                <td><strong>{{ $a->business_name }}</strong></td>
                <td>{{ $a->contact_person }}<div class="sub">{{ $a->email }} · {{ $a->phone }}</div></td>
                <td><span class="badge {{ $statusBadge($a->status) }}">{{ str_replace('_',' ',$a->status) }}</span></td>
                <td class="sub">{{ $a->created_at }}</td>
                <td>
                    <form method="post" action="/admin/applications/{{ $type }}/{{ $a->id }}/status" style="display:flex;gap:8px">@csrf
                        <select class="control" name="status" style="min-height:34px">
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected($a->status === $s)>{{ str_replace('_',' ',$s) }}</option>
                            @endforeach
                        </select>
                        <button class="btn" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No applications yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endforeach

<div class="note">Approving here sets pipeline status only. Converting an approved application into a vendor/distributor account runs through the onboarding API (<span class="mono">convert-to-vendor</span> / <span class="mono">convert-to-distributor</span>), which provisions accounts and permissions.</div>

@endsection
