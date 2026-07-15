@extends('pcb.layout')

@section('title', 'PCB Projects — NeoGiga PCB')
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
<section class="page">
    <div class="wrap">
        @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
        <header class="page-head">
            <div><div class="eyebrow">Private engineering workspace</div><h1 class="page-title">PCB projects</h1><p class="lead">Manage files, specifications, engineering quotes and production status.</p></div>
            <div class="actions"><a class="btn btn-primary" href="/en/projects/create">New PCB project</a></div>
        </header>

        <div class="grid kpis" aria-label="Project summary">
            <div class="kpi"><span>Active projects</span><strong>{{ number_format($summary['active']) }}</strong></div>
            <div class="kpi"><span>Quote stage</span><strong>{{ number_format($summary['quotes']) }}</strong></div>
            <div class="kpi"><span>Production stage</span><strong>{{ number_format($summary['manufacturing']) }}</strong></div>
        </div>

        @if($projects->count())
            <div class="grid projects">
                @foreach($projects as $project)
                    <a class="card project-card" href="/en/projects/{{ $project->id }}">
                        <div class="actions" style="justify-content:space-between"><span class="badge badge-{{ $project->status }}">{{ str_replace('_',' ',$project->status) }}</span><span class="muted">{{ $project->code }}</span></div>
                        <h2>{{ $project->name }}</h2>
                        <p class="muted" style="margin:0">{{ IlluminateSupportStr::limit($project->description ?: 'No project description added.', 120) }}</p>
                        <div class="project-meta">
                            <span>Quantity<b>{{ number_format($project->target_quantity) }}</b></span>
                            <span>Destination<b>{{ $project->destination_country ?: 'Not set' }}</b></span>
                            <span>Files<b>{{ $project->files_count }}</b></span>
                            <span>Updated<b>{{ $project->updated_at->diffForHumans() }}</b></span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div style="margin-top:20px">{{ $projects->links() }}</div>
        @else
            <div class="card empty"><strong>No PCB projects yet</strong><p>Create the first workspace for your board requirements and private design files.</p><div class="form-actions" style="justify-content:center"><a class="btn btn-primary" href="/en/projects/create">Create PCB project</a></div></div>
        @endif
    </div>
</section>
@endsection
