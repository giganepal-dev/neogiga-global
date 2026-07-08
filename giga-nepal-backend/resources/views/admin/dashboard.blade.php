@extends('admin.layout')
@section('title','Dashboard')
@section('crumb','Overview of the NeoGiga marketplace')
@section('content')

<div class="grid kpis">
    @php
        $cards = [
            ['Marketplaces', $stats['marketplaces'], 'regions live', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/>'],
            ['Categories', $stats['categories'], 'taxonomy nodes', '<path d="M3 6h18M3 12h18M3 18h12" stroke-linecap="round"/>'],
            ['Products', $stats['products'], 'in catalog', '<path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8" stroke-linejoin="round"/>'],
            ['Vendors', $stats['vendors'], 'registered', '<path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2" stroke-linecap="round"/><circle cx="9" cy="7" r="4"/>'],
            ['Users', $stats['users'], 'accounts', '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0112 0v1" stroke-linecap="round"/>'],
            ['Orders', $stats['orders'], 'all time', '<path d="M6 2l1.5 3M18 2l-1.5 3M3 6h18l-2 12H5L3 6z" stroke-linejoin="round"/>'],
        ];
    @endphp
    @foreach ($cards as [$label,$val,$sub,$icon])
        <div class="kpi">
            <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icon !!}</svg>{{ $label }}</div>
            <div class="v tnum">{{ number_format($val) }}</div>
            <div class="s">{{ $sub }}</div>
        </div>
    @endforeach
</div>

@if ($stats['products'] === 0)
    <div class="note">
        <strong>Catalog is empty.</strong> Reference data (marketplaces, currencies, {{ number_format($stats['categories']) }} categories) is seeded, but no products are loaded yet.
        Demo data is gated behind <span class="mono">SEED_DEMO=true</span>; real products should arrive via the import pipeline.
    </div>
@endif

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Marketplaces</h2><a class="btn btn-ghost" href="/admin/marketplaces">View all</a></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Name</th><th>Code</th><th>Currency</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($marketplaces as $m)
                    <tr>
                        <td><strong>{{ $m->name }}</strong></td>
                        <td class="mono">{{ $m->code }}</td>
                        <td>{{ $m->currency->code ?? '—' }}</td>
                        <td>@if($m->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty"><h3>No marketplaces</h3></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Top-level categories</h2><a class="btn btn-ghost" href="/admin/categories">Tree</a></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Category</th><th class="num">Sub</th></tr></thead>
                <tbody>
                @forelse ($rootCategories as $c)
                    <tr>
                        <td>{{ $c->name }} @if($c->is_featured)<span class="badge b-info">Featured</span>@endif</td>
                        <td class="num tnum">{{ number_format($c->children_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2"><div class="empty"><h3>No categories</h3></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
