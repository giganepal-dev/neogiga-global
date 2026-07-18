@extends('seller.layout')
@section('title', 'Dashboard')
@section('content')

@if (!empty($overview['alerts']))
    @foreach ($overview['alerts'] as $alert)
        <div class="badge b-warn" role="alert" style="margin:0 8px 12px 0">{{ $alert['message'] }}</div>
    @endforeach
@endif

<div class="kpis">
    <div class="kpi"><div class="t">Gross sales</div><div class="v tnum">{{ number_format($overview['orders']['gross_sales'], 2) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Net earnings</div><div class="v tnum">{{ number_format($overview['orders']['net_earnings'], 2) }}</div><div class="s">after commission</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v tnum">{{ number_format($overview['orders']['total_orders']) }}</div><div class="s">{{ number_format($overview['orders']['pending_orders']) }} pending</div></div>
    <div class="kpi"><div class="t">Products</div><div class="v tnum">{{ number_format($overview['products']['total_products']) }}</div><div class="s">{{ number_format($overview['products']['approved_products']) }} approved · {{ number_format($overview['products']['pending_products']) }} pending</div></div>
    <div class="kpi"><div class="t">Stock units</div><div class="v tnum">{{ number_format($overview['inventory']['available_units']) }}</div><div class="s">{{ number_format($overview['inventory']['reserved_units']) }} reserved</div></div>
    <div class="kpi"><div class="t">Pending payout</div><div class="v tnum">{{ number_format($overview['payouts']['pending_payout'], 2) }}</div><div class="s">{{ number_format($overview['payouts']['paid_payout'], 2) }} paid to date</div></div>
</div>

<div class="card">
    <div class="card-h"><h2>Marketplace approvals</h2><span class="badge b-muted">{{ $overview['marketplace_approvals']->count() }} applications</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Marketplace</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($overview['marketplace_approvals'] as $approval)
            <tr>
                <td>{{ $approval->marketplace_name ?? $approval->marketplace_code ?? '—' }}</td>
                <td><span class="badge {{ $approval->status === 'approved' ? 'b-ok' : ($approval->status === 'rejected' ? 'b-bad' : 'b-warn') }}">{{ ucfirst($approval->status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="2"><div class="empty">No marketplace applications yet — apply from your profile to start selling regionally.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Onboarding checklist</h2></div>
    <div style="padding:12px 16px;display:grid;gap:6px">
        @foreach ([
            'profile_created' => 'Company profile completed',
            'has_marketplace_application' => 'Marketplace application submitted',
            'has_warehouse' => 'Warehouse registered',
            'has_document' => 'Business documents uploaded',
            'is_verified' => 'Vendor verified by NeoGiga',
        ] as $key => $label)
            <div style="display:flex;align-items:center;gap:8px">
                @if ($overview['onboarding'][$key]) <x-icon name="approve" :size="16" style="color:var(--ok)" label="Done" /> @else <x-icon name="clock" :size="16" style="color:var(--warn)" label="Pending" /> @endif
                <span>{{ $label }}</span>
            </div>
        @endforeach
    </div>
</div>

@endsection
