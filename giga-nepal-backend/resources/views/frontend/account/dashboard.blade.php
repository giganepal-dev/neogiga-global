@extends('frontend.account.layout')
@section('title','My Account — NeoGiga')
@section('account-content')
<header class="account-topbar">
    <div><h1>Account overview</h1><p>Orders, sourcing and approved partner operations in one workspace.</p></div>
    <span class="account-region">{{ ($marketplaceContext['current']?->regional_brand_name ?? $marketplaceContext['current']?->name ?? 'NeoGiga Global') }} · {{ $marketplaceContext['currency_code'] ?? 'USD' }}</span>
</header>

<div class="account-stats">
    @foreach($stats as $stat)
        <article class="account-stat"><span>{{ $stat['label'] }}</span><strong>{{ number_format($stat['value']) }}</strong><a href="{{ $stat['url'] }}">View details →</a></article>
    @endforeach
</div>

<div class="account-grid">
    <section class="account-card">
        <div class="account-card-head"><h2>Recent orders</h2><a href="/account/orders">View all</a></div>
        @forelse($orders as $order)
            <div class="account-list-row"><div><strong>{{ $order->order_number ?? '#'.$order->id }}</strong><small>{{ optional(\Carbon\Carbon::parse($order->created_at ?? null))->diffForHumans() }}</small></div><span class="account-badge {{ $order->status ?? '' }}">{{ str_replace('_',' ',$order->status ?? 'pending') }}</span></div>
        @empty <div class="account-empty">No orders yet. <a href="/en/products">Browse parts</a></div> @endforelse
    </section>
    <section class="account-card">
        <div class="account-card-head"><h2>RFQ requests</h2><a href="/account/rfqs">View all</a></div>
        @forelse($rfqs as $rfq)
            <div class="account-list-row"><div><strong>{{ $rfq->rfq_number ?? 'RFQ #'.$rfq->id }}</strong><small>{{ $rfq->company_name ?? 'Personal request' }}</small></div><span class="account-badge {{ $rfq->status ?? '' }}">{{ str_replace('_',' ',$rfq->status ?? 'open') }}</span></div>
        @empty <div class="account-empty">No RFQs yet. <a href="/en/rfq">Create an RFQ</a></div> @endforelse
    </section>
    <section class="account-card">
        <div class="account-card-head"><h2>Quotations</h2><a href="/account/quotations">View all</a></div>
        @forelse($quotations as $quote)
            <div class="account-list-row"><div><strong>{{ $quote->quote_number ?? 'Quote #'.$quote->id }}</strong><small>{{ $quote->currency ?? 'USD' }} {{ number_format((float)($quote->grand_total ?? 0),2) }}</small></div><span class="account-badge {{ $quote->status ?? '' }}">{{ str_replace('_',' ',$quote->status ?? 'draft') }}</span></div>
        @empty <div class="account-empty">Quotations issued to your account will appear here.</div> @endforelse
    </section>
    <section class="account-card">
        <div class="account-card-head"><h2>Partner roles</h2><a href="/account/applications">Manage</a></div>
        @forelse($applications as $application)
            <div class="account-list-row"><div><strong>{{ ucwords(str_replace('_',' ',$application->role_key)) }}</strong><small>{{ $application->application_number }}</small></div><span class="account-badge {{ $application->status }}">{{ str_replace('_',' ',$application->status) }}</span></div>
        @empty <div class="account-empty">Apply for institutional, seller, distributor, manufacturer or fulfilment access.</div> @endforelse
    </section>
    <section class="account-card wide">
        <div class="account-card-head"><h2>PCB engineering projects</h2><a href="/account/pcb">View dashboard</a></div>
        @forelse($pcbProjects as $project)
            <div class="account-list-row"><div><strong>{{ $project->name }}</strong><small>{{ $project->code }} · Updated {{ $project->updated_at->diffForHumans() }}</small></div><span class="account-badge {{ $project->status }}">{{ str_replace('_',' ',$project->status) }}</span></div>
        @empty <div class="account-empty">No PCB projects yet. <a href="https://{{ config('pcb.domain', 'pcb.neogiga.com') }}/en/projects/create">Start a PCB project</a></div> @endforelse
    </section>
    <section class="account-card wide">
        <div class="account-card-head"><h2>Quick actions</h2></div>
        <div class="account-actions"><a class="account-button" href="/en/rfq">Create RFQ</a><a class="account-button secondary" href="/en/bom">Upload BOM</a><a class="account-button secondary" href="https://{{ config('pcb.domain', 'pcb.neogiga.com') }}/en/projects/create">Start PCB project</a><a class="account-button secondary" href="/account/support">Get support</a><a class="account-button gold" href="/account/applications">Add partner role</a></div>
    </section>
</div>
@endsection
