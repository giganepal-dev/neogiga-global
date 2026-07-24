@extends('seller.layout')
@section('title', 'RFQs')
@section('content')

<div class="page-intro">
    <h1>RFQs</h1>
    <p>Respond to request-for-quote assignments from buyers.</p>
</div>

<div class="card">
    <div class="card-h">
        <h2>Assigned RFQs</h2>
        <span class="badge b-muted">{{ number_format($assignments->total()) }} RFQs</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>RFQ Number</th>
                    <th>Company</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th>Assigned</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $a)
                <tr>
                    <td class="mono">{{ $a->rfq_number ?? '—' }}</td>
                    <td>
                        <strong>{{ $a->company_name ?? $a->contact_name ?? 'Individual buyer' }}</strong>
                        @if($a->contact_email)<div class="sub" style="font-size:.8rem">{{ $a->contact_email }}</div>@endif
                    </td>
                    <td class="num">
                        {{ $a->item_count ?? 0 }} item(s)
                        @if($a->total_quantity)
                            <div class="sub" style="font-size:.78rem">Qty: {{ number_format($a->total_quantity) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ in_array($a->status, ['bid_submitted']) ? 'b-ok' : ($a->status === 'declined' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst(str_replace('_', ' ', $a->status)) }}
                        </span>
                        <div class="sub" style="font-size:.75rem;margin-top:2px">RFQ: {{ ucfirst(str_replace('_', ' ', $a->rfq_status ?? 'open')) }}</div>
                    </td>
                    <td class="sub">
                        @if($a->deadline_at)
                            {{ \Illuminate\Support\Carbon::parse($a->deadline_at)->format('M j, Y') }}
                            @if(\Illuminate\Support\Carbon::parse($a->deadline_at)->isPast())
                                <span style="color:var(--bad);font-size:.78rem"> (overdue)</span>
                            @endif
                        @else
                            No deadline
                        @endif
                    </td>
                    <td class="sub">{{ \Illuminate\Support\Carbon::parse($a->invited_at ?? $a->created_at)->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty">
                            <h3>No RFQs assigned</h3>
                            <p>When buyers request quotes for your products, assignments will appear here.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assignments->hasPages())
    <div style="padding:12px 16px">{{ $assignments->links() }}</div>
    @endif
</div>

@endsection
