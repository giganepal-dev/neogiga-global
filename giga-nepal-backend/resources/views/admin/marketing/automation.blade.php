@extends('admin.layout')
@section('title','Automation Rules')
@section('crumb','Email automation and scheduled jobs')
@section('content')
<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">
<div class="card"><div class="card-h"><h2>Automation Rules</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Trigger</th><th>Delay</th><th>Status</th></tr></thead><tbody>@forelse($rules as $r)<tr><td><strong>{{ $r->name }}</strong></td><td class="mono">{{ $r->trigger }}</td><td>{{ $r->delay_minutes }} min</td><td>@if($r->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Paused</span>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No automation rules yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Scheduled Jobs</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Job</th><th>Schedule</th></tr></thead><tbody>@foreach($jobs as $job=>$schedule)<tr><td class="mono">{{ $job }}</td><td>{{ $schedule }}</td></tr>@endforeach</tbody></table></div></div>
</div>
@endsection
