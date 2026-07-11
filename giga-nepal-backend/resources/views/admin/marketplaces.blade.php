@extends('admin.layout')
@section('title','Marketplaces')
@section('crumb','Regional editions & host resolution')
@section('content')

<div class="card">
    <div class="card-h"><div><h2>Marketplaces</h2><div class="sub">{{ number_format($marketplaces->count()) }} regions</div></div></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Code</th><th>Country</th><th>Currency</th><th>Domain</th><th>Mode</th><th>Visibility</th><th>SEO</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($marketplaces as $m)
                <tr>
                    <td><a href="/admin/marketplaces/{{ $m->id }}/config"><strong>{{ $m->name }}</strong></a></td>
                    <td class="mono">{{ $m->code }}</td>
                    <td>{{ $m->country->name ?? '—' }}</td>
                    <td>{{ $m->currency->code ?? '—' }}</td>
                    <td class="mono">{{ $m->domain ?? $m->generated_domain ?? '—' }}</td>
                    <td><span class="badge b-muted">{{ $m->domain_mode ?? '—' }}</span></td>
                    <td>@if($m->is_visible)<span class="badge b-ok">Visible</span>@else<span class="badge b-warn">Hidden</span>@endif</td>
                    <td>@if(!empty($m->seo_title)&&!empty($m->seo_description))<span class="badge b-ok">Complete</span>@else<span class="badge b-warn">Incomplete</span>@endif</td>
                    <td>@if($m->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif</td>
                    <td><a class="btn" href="/admin/marketplaces/{{ $m->id }}/config">Configure</a></td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty"><h3>No marketplaces</h3><p>Run the MarketplaceSeeder.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
