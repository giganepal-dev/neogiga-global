@extends('admin.layout')
@section('title','Order Instalments')
@section('crumb','POS / Payment Plans')

@section('content')
<div class="grid kpis">
<div class="kpi"><span class="t">Total Instalments</span><span class="v">{{$instalments->count()}}</span></div>
<div class="kpi"><span class="t">Pending</span><span class="v">{{$instalments->where('status','pending')->count()}}</span></div>
<div class="kpi"><span class="t">Overdue</span><span class="v" style="color:var(--danger)">{{$instalments->where('status','overdue')->count()}}</span></div>
<div class="kpi"><span class="t">Paid</span><span class="v" style="color:var(--ok)">{{$instalments->where('status','paid')->count()}}</span></div>
</div>

<div class="card"><div class="card-h"><h2>Instalment Schedule</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Paid At</th><th>Method</th><th></th></tr></thead>
<tbody>@foreach($instalments as $i)
<tr>
<td class="mono">{{$i->order_number ?? '#' . $i->order_id}}</td>
<td>{{$i->customer_name ?? '—'}}</td>
<td class="num mono"><strong>{{number_format($i->amount, 2)}}</strong></td>
<td>{{$i->due_date}} @if($i->status === 'pending' && \Carbon\Carbon::parse($i->due_date)->isPast())<span class="badge b-danger">Overdue</span>@endif</td>
<td><span class="badge {{match($i->status){'paid'=>'b-ok','pending'=>'b-info','overdue'=>'b-danger','cancelled'=>'b-muted',default:'b-muted'} }}">{{ucfirst($i->status)}}</span></td>
<td>{{$i->paid_at ?? '—'}}</td>
<td>{{$i->payment_method ?? '—'}}</td>
<td>@if($i->status !== 'paid')<details class="modal"><summary class="btn btn-ghost" style="font-size:.72rem">Mark Paid</summary>
<div class="modal-panel"><div class="modal-h"><h3>Mark Instalment Paid</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/pos/instalments/{{$i->id}}/mark-paid">@csrf
<div class="field"><label>Payment Method</label><select class="control" name="payment_method" required><option value="cash">Cash</option><option value="card">Card</option><option value="wallet">Wallet</option><option value="transfer">Bank Transfer</option></select></div>
<div class="field"><label>Reference</label><input class="control" name="reference" placeholder="Transaction ID"></div>
<button class="btn btn-primary" type="submit">Confirm Payment</button></form></div></details>@endif</td>
</tr>@endforeach</tbody></table></div>
@if($instalments->isEmpty())<div class="empty"><h3>No instalments</h3><p>Order instalment plans will appear here.</p></div>@endif
</div>
@endsection
