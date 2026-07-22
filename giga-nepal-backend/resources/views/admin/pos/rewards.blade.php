@extends('admin.layout')
@section('title','Customer Rewards')
@section('crumb','POS / Loyalty & Rewards')

@section('page_actions')
<details class="modal"><summary class="btn btn-primary">Add Reward System</summary>
<div class="modal-panel"><div class="modal-h"><h3>Create Reward System</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/pos/rewards/systems">@csrf
<div class="field"><label>Name</label><input class="control" name="name" required placeholder="e.g. Standard Points"></div>
<div class="form-grid">
<div class="field"><label>Type</label><select class="control" name="type" required><option value="points">Points</option><option value="cashback">Cashback</option><option value="discount">Discount</option></select></div>
<div class="field"><label>Min Order</label><input class="control" name="min_order" type="number" step="any" placeholder="No minimum"></div>
</div>
<div class="form-grid">
<div class="field"><label>Spend Target (to earn)</label><input class="control" name="target" type="number" step="any" value="100" required></div>
<div class="field"><label>Reward Per Target</label><input class="control" name="reward_value" type="number" step="any" value="1" required></div>
</div>
<button class="btn btn-primary" type="submit">Save Reward System</button></form></div></details>
@endsection

@section('content')

<div class="grid split">
{{-- Reward Systems --}}
<div class="card"><div class="card-h"><h2>Reward Systems ({{$systems->count()}})</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Spend → Earn</th><th>Min Order</th><th>Status</th><th></th></tr></thead>
<tbody>@foreach($systems as $s)<tr>
<td><strong>{{$s->name}}</strong></td><td>{{$s->type}}</td>
<td class="mono">{{number_format($s->target, 2)}} → {{number_format($s->reward_value, 2)}}</td>
<td class="mono">{{$s->min_order ? number_format($s->min_order, 2) : '—'}}</td>
<td><span class="badge {{($s->is_active ?? 1) ? 'b-ok':'b-muted'}}">{{($s->is_active ?? 1) ? 'Active':'Inactive'}}</span></td>
<td><form method="post" action="/admin/pos/rewards/systems/{{$s->id}}/toggle">@csrf<button class="btn btn-ghost" style="font-size:.72rem">Toggle</button></form></td>
</tr>@endforeach</tbody></table></div></div>

{{-- Customer Balances --}}
<div class="card"><div class="card-h"><h2>Customer Balances</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Customer</th><th>System</th><th>Earned</th><th>Redeemed</th><th>Balance</th><th>Last Earned</th></tr></thead>
<tbody>@foreach($customerRewards as $cr)<tr>
<td><strong>{{$cr->customer_name}}</strong><br><span style="font-size:.72rem;color:var(--muted)">{{$cr->email}}</span></td>
<td>{{$cr->system_name}}</td>
<td class="num mono">{{number_format($cr->points_earned, 2)}}</td>
<td class="num mono">{{number_format($cr->points_redeemed, 2)}}</td>
<td class="num"><strong>{{number_format($cr->current_balance, 2)}}</strong></td>
<td>{{$cr->last_earned_at ?? '—'}}</td>
</tr>@endforeach</tbody></table></div></div>
</div>
@endsection
