@extends('admin.layout')
@section('title','PCB Projects')
@section('crumb','Commerce / PCB engineering')
@section('page_actions')<a class="btn btn-ghost" href="https://pcb.neogiga.com/en" target="_blank" rel="noopener">Open PCB portal</a>@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Total projects</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">All customer PCB workspaces</div></div>
    <div class="kpi"><div class="t">Needs review</div><div class="v tnum">{{ number_format($stats['review']) }}</div><div class="s">Files, requirements or quote review</div></div>
    <div class="kpi"><div class="t">Customer approval</div><div class="v tnum">{{ number_format($stats['quoted']) }}</div><div class="s">Quoted or awaiting approval</div></div>
    <div class="kpi"><div class="t">Production flow</div><div class="v tnum">{{ number_format($stats['production']) }}</div><div class="s">Ordered through shipped</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>PCB project queue</h2><div class="sub">Customer workspaces, private files and manual engineering quotes</div></div></div>
    <form class="filters" method="get">
        <div class="field"><label for="q">Search</label><input class="control" id="q" name="q" value="{{ request('q') }}" placeholder="Project name or code"></div>
        <div class="field"><label for="status">Status</label><select class="control" id="status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
        <div class="actions" style="align-self:end"><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-ghost" href="/admin/pcb">Reset</a></div>
    </form>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Project</th><th>Customer</th><th>Status</th><th>Files</th><th>Quotes</th><th>Destination</th><th>Updated</th><th></th></tr></thead><tbody>
    @forelse($projects as $project)
        <tr>
            <td><strong>{{ $project->name }}</strong><div class="sub">{{ $project->code }}</div></td>
            <td>{{ $project->user?->name }}<div class="sub">{{ $project->user?->email }}</div></td>
            <td><span class="badge {{ in_array($project->status,['completed','shipped'],true) ? 'b-ok' : (in_array($project->status,['cancelled','on_hold'],true) ? 'b-danger' : (in_array($project->status,['quote_pending','requirements_pending'],true) ? 'b-warn' : 'b-info')) }}">{{ str_replace('_',' ',$project->status) }}</span></td>
            <td>{{ $project->files_count }}</td><td>{{ $project->quote_configurations_count }}</td><td>{{ $project->destination_country ?: '—' }}</td><td>{{ $project->updated_at->diffForHumans() }}</td>
            <td><a class="btn btn-ghost" href="/admin/pcb/projects/{{ $project->id }}">Review</a></td>
        </tr>
    @empty
        <tr><td colspan="8"><div class="empty"><h3>No PCB projects match this view</h3><p>New projects created on pcb.neogiga.com will appear here.</p></div></td></tr>
    @endforelse
    </tbody></table></div>
    @if($projects->hasPages())<div style="padding:14px 16px">{{ $projects->links() }}</div>@endif
</section>
@endsection
