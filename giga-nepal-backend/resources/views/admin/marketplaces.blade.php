@extends('admin.layout')
@section('title','Marketplaces')
@section('crumb','Regional editions & host resolution')
@section('content')

<div class="card">
    <div class="card-h"><div><h2>Marketplaces</h2><div class="sub">{{ number_format($marketplaces->count()) }} regions</div></div></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Code</th><th>Country</th><th>Currency</th><th>Domains</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($marketplaces as $m)
                <tr>
                    <td><strong>{{ $m->name }}</strong></td>
                    <td class="mono">{{ $m->code }}</td>
                    <td>{{ $m->country->name ?? '—' }}</td>
                    <td>{{ $m->currency->code ?? '—' }}</td>
                    <td>
                        @forelse ($m->domains as $d)
                            <span class="badge b-muted mono">{{ $d->domain }}</span>
                        @empty <span style="color:var(--muted)">—</span> @endforelse
                    </td>
                    <td>@if($m->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No marketplaces</h3><p>Run the MarketplaceSeeder.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
