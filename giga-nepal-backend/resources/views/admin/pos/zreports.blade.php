@extends('admin.layout')
@section('title','Z-Reports')
@section('crumb','POS / End-of-Day Z-Reports')

@section('content')
<div class="grid kpis">
<div class="kpi"><span class="t">Total Reports</span><span class="v">{{$reports->count()}}</span></div>
<div class="kpi"><span class="t">Total Sales</span><span class="v">{{number_format($reports->sum('total_sales'), 2)}}</span></div>
<div class="kpi"><span class="t">Avg Difference</span><span class="v {{abs($reports->avg('difference') ?? 0) > 10 ? 'danger' : ''}}">{{number_format($reports->avg('difference') ?? 0, 2)}}</span></div>
</div>

@foreach($reports as $r)
<details class="card stack-gap" style="margin-bottom:12px"><summary class="card-h" style="cursor:pointer"><div><strong>{{$r->register_name}}</strong> — {{$r->report_date}} <span class="badge {{abs($r->difference) > 10 ? 'b-danger' : 'b-ok'}}">Diff: {{number_format($r->difference, 2)}}</span></div><span style="color:var(--muted);font-size:.78rem">Closed by {{$r->closed_by_name ?? '—'}}</span></summary>
<div class="card-body">
<div class="grid kpis">
<div class="kpi"><span class="t">Opening</span><span class="v">{{number_format($r->opening_balance, 2)}}</span></div>
<div class="kpi"><span class="t">Expected</span><span class="v">{{number_format($r->expected_balance, 2)}}</span></div>
<div class="kpi"><span class="t">Closing</span><span class="v">{{number_format($r->closing_balance, 2)}}</span></div>
<div class="kpi"><span class="t">Difference</span><span class="v {{abs($r->difference) > 10 ? 'danger' : ''}}">{{number_format($r->difference, 2)}}</span></div>
</div>
<div class="grid split">
<div>
<h3 style="font-size:.9rem;margin:0 0 8px">Sales</h3>
<div class="grid kpis">
<div class="kpi"><span class="t">Cash</span><span class="v">{{number_format($r->cash_sales, 2)}}</span></div>
<div class="kpi"><span class="t">Card</span><span class="v">{{number_format($r->card_sales, 2)}}</span></div>
<div class="kpi"><span class="t">Wallet</span><span class="v">{{number_format($r->wallet_sales, 2)}}</span></div>
<div class="kpi"><span class="t">Count</span><span class="v">{{$r->sale_count}}</span><span class="s">sales</span></div>
</div>
</div>
<div>
<h3 style="font-size:.9rem;margin:0 0 8px">Other</h3>
<div class="grid kpis">
<div class="kpi"><span class="t">Refunds</span><span class="v">{{number_format($r->total_refunds, 2)}}</span><span class="s">{{$r->refund_count}} refunds</span></div>
<div class="kpi"><span class="t">Cash In</span><span class="v">{{number_format($r->cash_in, 2)}}</span></div>
<div class="kpi"><span class="t">Cash Out</span><span class="v">{{number_format($r->cash_out, 2)}}</span></div>
</div>
</div>
</div>
</div></details>
@endforeach
@if($reports->isEmpty())<div class="empty"><h3>No Z-reports yet</h3><p>Close a register to generate a Z-report.</p></div>@endif
@endsection
