@extends('admin.layout')
@section('title','Register History')
@section('crumb','POS / Register Action History')

@section('content')
<div class="card"><div class="card-h"><h2>Register Action Log ({{$history->count()}} entries)</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Time</th><th>Register</th><th>Action</th><th>Amount</th><th>Balance Before</th><th>Balance After</th><th>User</th><th>Description</th></tr></thead>
<tbody>@foreach($history as $h)
<tr>
<td class="mono">{{$h->created_at}}</td>
<td>{{$h->register_name}}</td>
<td><span class="badge {{match($h->action){'open'=>'b-ok','close'=>'b-danger','cash_in'=>'b-info','cash_out'=>'b-warn','sale'=>'b-ok','refund'=>'b-danger',default:'b-muted'} }}">{{$h->action}}</span></td>
<td class="num mono {{$h->amount < 0 ? 'danger' : ''}}">{{number_format($h->amount, 2)}}</td>
<td class="num mono">{{number_format($h->balance_before, 2)}}</td>
<td class="num mono">{{number_format($h->balance_after, 2)}}</td>
<td>{{$h->user_name ?? '—'}}</td>
<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis">{{$h->description ?? '—'}}</td>
</tr>@endforeach</tbody></table></div>
@if($history->isEmpty())<div class="empty"><h3>No history yet</h3><p>Register actions (open, close, sales) will appear here.</p></div>@endif
</div>
@endsection
