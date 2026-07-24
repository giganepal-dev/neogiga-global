@extends('seller.layout')
@section('title', 'Quotations')
@section('content')

<div class="page-intro">
    <h1>Quotations</h1>
    <p>Manage quotations you have created for buyers.</p>
</div>

<div class="card">
    <div class="card-h">
        <h2>My Quotations</h2>
        <span class="badge b-muted">{{ number_format($quotations->total()) }} quotations</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Quote Number</th>
                    <th>RFQ</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th class="num">Grand Total</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotations as $q)
                <tr>
                    <td class="mono">{{ $q->quote_number }}</td>
                    <td class="mono sub">{{ $q->rfq_number ?? '—' }}</td>
                    <td>
                        @if($q->company_name)
                            <strong>{{ $q->company_name }}</strong>
                        @elseif($q->user_id)
                            <span class="sub">User #{{ $q->user_id }}</span>
                        @else
                            <span class="sub">—</span>
                        @endif
                    </td>
                    <td class="num">{{ $q->item_count ?? 0 }}</td>
                    <td class="num tnum">
                        {{ number_format($q->grand_total, 2) }}
                        <span class="sub" style="font-size:.75rem">{{ $q->currency }}</span>
                    </td>
                    <td class="sub">
                        @if($q->valid_until)
                            {{ \Illuminate\Support\Carbon::parse($q->valid_until)->format('M j, Y') }}
                            @if(\Illuminate\Support\Carbon::parse($q->valid_until)->isPast())
                                <span style="color:var(--bad);font-size:.78rem"> (expired)</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ in_array($q->status, ['accepted', 'sent']) ? 'b-ok' : ($q->status === 'rejected' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst($q->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <h3>No quotations yet</h3>
                            <p>Create quotations in response to RFQ assignments to start selling.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quotations->hasPages())
    <div style="padding:12px 16px">{{ $quotations->links() }}</div>
    @endif
</div>

@endsection
