@extends('seller.layout')
@section('title', 'Statements')
@section('content')

<div class="page-intro">
    <h1>Financial Statements</h1>
    <p>View your payment history and settlement records.</p>
</div>

{{-- Summary --}}
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:16px">
    <div class="kpi">
        <div class="t">Total Paid</div>
        <div class="v" style="color:var(--ok)">${{ number_format($summary->total_paid ?? 0, 2) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Pending</div>
        <div class="v" style="color:var(--warn)">${{ number_format($summary->total_pending ?? 0, 2) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Total Payouts</div>
        <div class="v">{{ number_format($summary->total_count ?? 0) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Payment History</h2>
        <span class="badge b-muted">{{ number_format($statements->total()) }} statements</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Payout Number</th>
                    <th>Status</th>
                    <th>Currency</th>
                    <th class="num">Gross</th>
                    <th class="num">Commission</th>
                    <th class="num">Fees</th>
                    <th class="num">Net Amount</th>
                    <th>Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statements as $s)
                <tr>
                    <td class="mono">{{ $s->payout_number ?? '#' . $s->id }}</td>
                    <td>
                        <span class="badge {{ $s->status === 'paid' ? 'b-ok' : ($s->status === 'cancelled' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                    <td>{{ $s->currency_code ?? 'USD' }}</td>
                    <td class="num tnum">${{ number_format($s->gross_amount ?? 0, 2) }}</td>
                    <td class="num tnum" style="color:var(--bad)">-${{ number_format($s->commission_amount ?? 0, 2) }}</td>
                    <td class="num tnum" style="color:var(--bad)">-${{ number_format($s->fee_amount ?? 0, 2) }}</td>
                    <td class="num tnum" style="font-weight:600">${{ number_format($s->net_amount ?? 0, 2) }}</td>
                    <td class="sub">{{ $s->paid_at ? \Illuminate\Support\Carbon::parse($s->paid_at)->format('M j, Y') : '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty">
                            <h3>No statements yet</h3>
                            <p>Payment statements will appear here once payouts are processed.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($statements->hasPages())
    <div style="padding:12px 16px">{{ $statements->links() }}</div>
    @endif
</div>

@endsection
