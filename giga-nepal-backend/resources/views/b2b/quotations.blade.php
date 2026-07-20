@extends('b2b.layout')
@section('title','Quotations')
@section('content')
<div class="page-intro">
    <h1>Official Quotations</h1>
    <p>Review quotations from NeoGiga, accept to unlock payment, then complete checkout with your regional payment methods.</p>
</div>
@if($quotations->isEmpty())
    <div class="card empty-card"><p>No quotations yet. Submit an RFQ to receive an official quote.</p><a href="/b2b/rfqs/create" class="btn btn-primary">New Quote Request</a></div>
@else
    <div class="card"><div class="table-wrap"><table class="table">
        <thead><tr><th>Quotation #</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th><th>Valid until</th><th></th></tr></thead>
        <tbody>
            @foreach($quotations as $q)
            <tr>
                <td class="mono"><strong>{{ $q->quotation_number }}</strong></td>
                <td>{{ $q->items_count }}</td>
                <td class="tnum">{{ $q->currency_code }} {{ number_format($q->grand_total, 2) }}</td>
                <td><span class="badge b-info">{{ $q->status }}</span></td>
                <td><span class="badge {{ $q->payment_status === 'unlocked' ? 'b-ok' : 'b-muted' }}">{{ $q->payment_status }}</span></td>
                <td class="sub">{{ $q->valid_until?->format('M j, Y') }}</td>
                <td><a href="/b2b/quotations/{{ $q->id }}" class="btn btn-ghost">View</a></td>
            </tr>
            @endforeach
        </tbody>
    </table></div></div>
    {{ $quotations->links() }}
@endif
@endsection
