@extends('admin.layout')
@section('title','Abandoned Carts')
@section('crumb','Recovery pipeline')
@section('content')
<div class="grid kpis"><div class="kpi"><div class="t">Open value</div><div class="v tnum">{{ number_format($openValue,2) }}</div><div class="s">cart currency mixed</div></div><div class="kpi"><div class="t">Recovered</div><div class="v tnum">{{ number_format($recoveredValue,2) }}</div><div class="s">tracked revenue</div></div><div class="kpi"><div class="t">Carts</div><div class="v tnum">{{ number_format($carts->total()) }}</div><div class="s">records</div></div></div>
<div class="card"><div class="card-h"><h2>Abandoned Cart Records</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Email</th><th>Total</th><th>Status</th><th>Abandoned</th></tr></thead><tbody>@forelse($carts as $c)<tr><td class="mono">{{ $c->email ?? '—' }}</td><td class="num tnum">{{ $c->currency_code }} {{ number_format($c->cart_total,2) }}</td><td><span class="badge b-muted">{{ $c->status }}</span></td><td>{{ $c->abandoned_at ?? '—' }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No abandoned carts yet</h3></div></td></tr>@endforelse</tbody></table></div>@if($carts->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $carts->links() }}</div>@endif</div>
@endsection
