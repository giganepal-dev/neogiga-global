@extends('b2b.layout')
@section('title','RFQs')
@section('content')
<h1 style="margin:0 0 24px">RFQ Requests</h1>
@if($rfqs->isEmpty())
    <div class="card" style="text-align:center;padding:40px"><p style="color:var(--muted)">No RFQs yet.</p></div>
@else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>RFQ #</th><th>Items</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>@foreach($rfqs as $r)<tr>
            <td class="mono"><strong>{{ $r->rfq_number ?? '#'.$r->id }}</strong></td>
            <td>{{ $r->item_count ?? '—' }}</td>
            <td><span class="badge b-info">{{ $r->status ?? 'pending' }}</span></td>
            <td style="font-size:.78rem;color:var(--faint)">{{ $r->created_at ?? '—' }}</td>
        </tr>@endforeach</tbody>
    </table></div>
    {{ $rfqs->links() }}
@endif
@endsection
