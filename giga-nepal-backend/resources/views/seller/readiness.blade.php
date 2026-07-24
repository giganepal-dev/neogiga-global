@extends('seller.layout')
@section('title', 'Readiness & Onboarding')
@section('content')

<div class="page-intro">
    <h1>Readiness & Onboarding</h1>
    <p>Complete the checklist below to activate your seller account and start selling on NeoGiga.</p>
</div>

{{-- Progress Overview --}}
<div class="card">
    <div class="card-h">
        <h2>Onboarding Progress</h2>
        <span class="badge {{ $progressPercent === 100 ? 'b-ok' : 'b-info' }}">{{ $progressPercent }}%</span>
    </div>
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span class="sub">{{ $doneCount }} of {{ $total }} steps completed</span>
            @if($progressPercent === 100)
                <span class="badge b-ok">Ready to sell!</span>
            @else
                <span class="badge b-warn">{{ $total - $doneCount }} steps remaining</span>
            @endif
        </div>
        <div style="width:100%;height:10px;background:rgba(100,116,139,.15);border-radius:5px;overflow:hidden">
            <div style="width:{{ $progressPercent }}%;height:100%;background:{{ $progressPercent === 100 ? 'var(--ok)' : 'var(--accent)' }};border-radius:5px;transition:width .4s"></div>
        </div>
    </div>
</div>

{{-- Status Summary --}}
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:16px">
    <div class="kpi">
        <div class="t">Verification</div>
        <div class="v" style="color:{{ $isVerified ? 'var(--ok)' : 'var(--warn)' }}">{{ $isVerified ? 'Verified' : 'Pending' }}</div>
        <div class="s">{{ $isVerified ? 'Account fully verified' : 'Awaiting admin review' }}</div>
    </div>
    <div class="kpi">
        <div class="t">Marketplace Access</div>
        <div class="v" style="color:{{ $hasApprovedMarketplace ? 'var(--ok)' : 'var(--warn)' }}">{{ $hasApprovedMarketplace ? 'Approved' : 'None' }}</div>
        <div class="s">{{ $marketplaceApprovals->count() }} application(s)</div>
    </div>
    <div class="kpi">
        <div class="t">Profile Completion</div>
        <div class="v">{{ round(($doneCount / $total) * 100) }}%</div>
        <div class="s">{{ $doneCount }}/{{ $total }} checks passed</div>
    </div>
</div>

{{-- Checklist --}}
<div class="card">
    <div class="card-h"><h2>Onboarding Checklist</h2></div>
    <div class="card-body" style="padding:0">
        @foreach($checklist as $item)
        <a href="{{ $item['link'] }}" style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 20px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:background .15s"
           onmouseover="this.style.background='rgba(127,127,127,.05)'" onmouseout="this.style.background='transparent'">
            <div style="display:flex;align-items:center;gap:12px;min-width:0">
                <span style="width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:.75rem;flex-shrink:0;background:{{ $item['done'] ? 'var(--ok)' : 'rgba(100,116,139,.2)' }};color:#fff">
                    {{ $item['done'] ? '✓' : '' }}
                </span>
                <div style="min-width:0">
                    <div style="font-weight:600;font-size:.95rem">{{ $item['label'] }}</div>
                    <div class="sub" style="font-size:.82rem;margin-top:2px">{{ $item['detail'] }}</div>
                </div>
            </div>
            <span class="badge {{ $item['done'] ? 'b-ok' : 'b-muted' }}" style="flex-shrink:0">
                {{ $item['done'] ? 'Complete' : 'Required' }}
            </span>
        </a>
        @endforeach
    </div>
</div>

{{-- Marketplace Applications --}}
@if($marketplaceApprovals->isNotEmpty())
<div class="card">
    <div class="card-h"><h2>Marketplace Applications</h2><a href="/seller/marketplace" class="sub">Apply →</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Marketplace</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Reviewed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marketplaceApprovals as $approval)
                <tr>
                    <td><strong>{{ $approval->marketplace_name ?? 'Marketplace #' . $approval->marketplace_id }}</strong></td>
                    <td>
                        <span class="badge {{ $approval->status === 'approved' ? 'b-ok' : ($approval->status === 'rejected' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst($approval->status) }}
                        </span>
                    </td>
                    <td class="sub">{{ \Illuminate\Support\Carbon::parse($approval->created_at)->diffForHumans() }}</td>
                    <td class="sub">{{ $approval->reviewed_at ? \Illuminate\Support\Carbon::parse($approval->reviewed_at)->diffForHumans() : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty">No applications submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Next Steps --}}
@if($progressPercent < 100)
<div class="card">
    <div class="card-h"><h2>Next Steps</h2></div>
    <div class="card-body">
        <div style="display:grid;gap:8px">
            @foreach($checklist->where('done', false) as $item)
            <a href="{{ $item['link'] }}" class="btn btn-ghost" style="justify-content:start">
                <x-icon name="arrow-right" :size="16" /> {{ $item['label'] }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@endsection
