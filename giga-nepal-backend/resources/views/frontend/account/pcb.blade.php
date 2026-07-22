@extends('frontend.account.layout')
@section('title','PCB Projects — My Account — NeoGiga')
@section('account-content')
<header class="account-topbar">
    <div><h1>PCB project dashboard</h1><p>Track private design work, engineering quotes and production from your unified NeoGiga account.</p></div>
    <div class="account-actions"><a class="account-button secondary" href="{{ $pcbBaseUrl }}/projects">Full PCB workspace</a><a class="account-button" href="{{ $pcbBaseUrl }}/projects/create">New PCB project</a></div>
</header>

<div class="account-stats">
    <article class="account-stat"><span>Total projects</span><strong>{{ number_format($summary['total']) }}</strong><small>Owned and shared</small></article>
    <article class="account-stat"><span>Active projects</span><strong>{{ number_format($summary['active']) }}</strong><small>Design through delivery</small></article>
    <article class="account-stat"><span>Quote stage</span><strong>{{ number_format($summary['quotes']) }}</strong><small>Pending customer action</small></article>
    <article class="account-stat"><span>Production stage</span><strong>{{ number_format($summary['production']) }}</strong><small>Ordered through shipped</small></article>
</div>

<section class="account-panel">
    <div class="account-panel-head">
        <div><h2>Your PCB workspaces</h2><p>Only projects you own, actively share, or access through your organization are shown.</p></div>
    </div>
    @if($projects->count())
        <div class="account-pcb-grid">
            @foreach($projects as $project)
                <article class="account-pcb-card">
                    <div class="account-pcb-meta"><span class="account-badge {{ $project->status }}">{{ str_replace('_',' ',$project->status) }}</span><span>{{ $project->code }}</span></div>
                    <h3>{{ $project->name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($project->description ?: 'No project description added.', 130) }}</p>
                    <dl>
                        <div><dt>Access</dt><dd>{{ $project->account_access_role }}</dd></div>
                        <div><dt>Quantity</dt><dd>{{ number_format((int) $project->target_quantity) }}</dd></div>
                        <div><dt>Files</dt><dd>{{ number_format($project->files_count) }}</dd></div>
                        <div><dt>Quotes</dt><dd>{{ number_format($project->quote_configurations_count) }}</dd></div>
                        <div><dt>Destination</dt><dd>{{ $project->destination_country ?: 'Not set' }}</dd></div>
                        <div><dt>Updated</dt><dd>{{ $project->updated_at->diffForHumans() }}</dd></div>
                    </dl>
                    <a class="account-button secondary" href="{{ $pcbBaseUrl }}/projects/{{ $project->id }}">Open engineering workspace →</a>
                </article>
            @endforeach
        </div>
        <div class="account-pagination">{{ $projects->links() }}</div>
    @else
        <div class="account-empty"><strong>No PCB projects yet</strong><br>Start a secure workspace for fabrication, assembly, files and engineering quotes.<div class="account-actions account-empty-actions"><a class="account-button" href="{{ $pcbBaseUrl }}/projects/create">Create PCB project</a></div></div>
    @endif
</section>
@endsection
