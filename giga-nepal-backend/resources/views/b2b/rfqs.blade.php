@extends('b2b.layout')
@section('title','RFQs')
@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Quote Requests</h1>
        <p>Track institutional RFQs and official quotations from NeoGiga.</p>
    </div>
    <a href="/b2b/rfqs/create" class="btn btn-primary">New Quote Request</a>
</div>
@if($rfqs->isEmpty())
    <div class="card empty-card">
        <p>No quote requests yet.</p>
        <a href="/b2b/rfqs/create" class="btn btn-primary">Submit your first RFQ</a>
    </div>
@else
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>RFQ #</th><th>Items</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($rfqs as $rfq)
                    <tr>
                        <td class="mono"><strong>{{ $rfq->rfq_number }}</strong></td>
                        <td>{{ $rfq->items_count }}</td>
                        <td><span class="badge b-info">{{ $rfq->status }}</span></td>
                        <td class="sub">{{ $rfq->created_at?->format('M j, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{ $rfqs->links() }}
@endif
@endsection
