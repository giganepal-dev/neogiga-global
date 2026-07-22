@extends('pcb.layout')

@section('title', 'PCB Projects — NeoGiga PCB')
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
@if(session('status'))<div class="wrap" style="padding-top:16px"><div class="notice">{{ session('status') }}</div></div>@endif
<section style="padding:28px 0 64px">
    <div class="wrap">
        <header style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap">
            <div><div class="eyebrow">Private engineering workspace</div><h1 class="page-title" style="margin:5px 0 6px">PCB projects</h1><p class="lead" style="max-width:72ch">Manage design files, specifications, engineering quotes and production status.</p></div>
            <div class="form-actions" style="margin-top:0"><a class="btn btn-ghost" href="https://neogiga.com/account/pcb">Customer dashboard</a><a class="btn btn-primary" href="/en/projects/create">New PCB project</a></div>
        </header>

        <div class="grid kpis">
            <div class="kpi"><span class="t">Active projects</span><span class="v">{{ number_format($summary['active']) }}</span><span class="s">Draft through production</span></div>
            <div class="kpi"><span class="t">Quote stage</span><span class="v">{{ number_format($summary['quotes']) }}</span><span class="s">Pending or quoted</span></div>
            <div class="kpi"><span class="t">Production stage</span><span class="v">{{ number_format($summary['manufacturing']) }}</span><span class="s">Ordered through shipped</span></div>
            <div class="kpi"><span class="t">Total projects</span><span class="v">{{ number_format($projects->total()) }}</span><span class="s">All workspaces</span></div>
        </div>

        @if($projects->count())
            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
                @foreach($projects as $project)
                    @php
                        $statusColors = ['draft'=>'b-muted','cancelled'=>'b-danger','on_hold'=>'b-danger','quote_pending'=>'b-warn','requirements_pending'=>'b-warn','files_ready'=>'b-warn','quoted'=>'b-info','awaiting_approval'=>'b-info','ordered'=>'b-info','manufacturing'=>'b-info','design_in_progress'=>'b-info','inspection'=>'b-warn','design_review'=>'b-warn','shipped'=>'b-ok','completed'=>'b-ok','design_approved'=>'b-ok','approved'=>'b-ok'];
                        $badgeClass = $statusColors[$project->status] ?? 'b-muted';
                    @endphp
                    <a class="card" href="/en/projects/{{ $project->id }}" style="padding:20px;display:grid;gap:12px;transition:border-color .2s,transform .2s">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$project->status) }}</span>
                            <span class="mono" style="font-size:.75rem;color:var(--faint)">{{ $project->code }}</span>
                        </div>
                        <h2 style="font-size:1.05rem;margin:0;font-weight:700">{{ $project->name }}</h2>
                        <p style="color:var(--muted);font-size:.84rem;margin:0">{{ \Illuminate\Support\Str::limit($project->description ?: 'No project description added.', 120) }}</p>
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;color:var(--muted);font-size:.82rem">
                            <span>Quantity<span style="display:block;color:var(--on);font-weight:600;margin-top:2px">{{ number_format($project->target_quantity) }}</span></span>
                            <span>Destination<span style="display:block;color:var(--on);font-weight:600;margin-top:2px">{{ $project->destination_country ?: 'Not set' }}</span></span>
                            <span>Files<span style="display:block;color:var(--on);font-weight:600;margin-top:2px">{{ $project->files_count }}</span></span>
                            <span>Updated<span style="display:block;color:var(--on);font-weight:600;margin-top:2px">{{ $project->updated_at->diffForHumans() }}</span></span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:20px">{{ $projects->links() }}</div>
        @else
            <div class="card" style="padding:48px 20px;text-align:center">
                <strong style="display:block;font-size:1.05rem;color:var(--on);margin-bottom:6px">No PCB projects yet</strong>
                <p class="muted">Create the first workspace for your board requirements and private design files.</p>
                <div class="form-actions" style="justify-content:center"><a class="btn btn-primary" href="/en/projects/create">Create PCB project</a></div>
            </div>
        @endif
    </div>
</section>
@endsection
