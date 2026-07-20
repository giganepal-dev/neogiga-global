@extends('distributor.layout')
@section('title','Orders')
@section('content')
<div class="page-intro"><h1>Orders</h1><p>Orders routed through your distributor account.</p></div>
@if($orders->isEmpty())
<div class="card"><div class="card-body sub">No orders yet.</div></div>
@else
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Reference</th><th>Status</th><th>Gross</th><th>Commission</th><th>Date</th></tr></thead>
    <tbody>@foreach($orders as $o)<tr>
        <td class="mono"><strong>{{ $o->order_reference ?? ($o->order_number ?? '#'.$o->id) }}</strong></td>
        <td><span class="badge b-info">{{ $o->status ?? 'pending' }}</span></td>
        <td>@if(isset($o->gross_amount))${{ number_format($o->gross_amount, 2) }}@elseif(isset($o->grand_total))${{ number_format($o->grand_total, 2) }}@else—@endif</td>
        <td>@if(isset($o->commission_amount))${{ number_format($o->commission_amount, 2) }}@else—@endif</td>
        <td class="sub">{{ $o->created_at ?? '—' }}</td>
    </tr>@endforeach</tbody>
</table></div>{{ $orders->links() }}</div>
@endif
@endsection
