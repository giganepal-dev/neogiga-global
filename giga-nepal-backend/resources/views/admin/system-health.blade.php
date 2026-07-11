@extends('admin.layout')
@section('title','System Health')
@section('crumb','Live platform, catalog, queue, cache, storage and import status')
@section('content')

@php
    $fmt = function ($bytes) {
        $bytes = (float) $bytes;
        if ($bytes <= 0) return 'n/a';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power ? 2 : 0) . ' ' . $units[$power];
    };
    $badge = fn($ok) => $ok === true ? 'b-ok' : ($ok === null ? 'b-muted' : 'b-danger');
    $label = fn($ok) => $ok === true ? 'OK' : ($ok === null ? 'Not configured' : 'Needs attention');
@endphp

<div class="grid kpis">
    @foreach($services as $name => $ok)
        <div class="kpi">
            <div class="t">{{ $name }}</div>
            <div class="v"><span class="badge {{ $badge($ok) }}">{{ $label($ok) }}</span></div>
            <div class="s">checked {{ $checkedAt->format('Y-m-d H:i:s') }}</div>
        </div>
    @endforeach
</div>

<div class="grid dashboard-split">
    <section class="card">
        <div class="card-h"><h2>Application Runtime</h2><span class="sub">environment and public health endpoint</span></div>
        <div class="scroll-x"><table class="tbl">
            <tbody>
                <tr><th>Environment</th><td>{{ app()->environment() }}</td></tr>
                <tr><th>Debug Mode</th><td><span class="badge {{ config('app.debug') ? 'b-danger' : 'b-ok' }}">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</span></td></tr>
                <tr><th>App URL</th><td class="mono">{{ config('app.url') }}</td></tr>
                <tr><th>Health Endpoint</th><td><span class="badge {{ $api['ok'] ? 'b-ok' : 'b-danger' }}">{{ $api['endpoint'] }} {{ $api['status'] }}</span></td></tr>
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Database</h2><span class="badge {{ $database['ok'] ? 'b-ok' : 'b-danger' }}">{{ $database['driver'] }}</span></div>
        <div class="scroll-x"><table class="tbl">
            <tbody>
                <tr><th>Status</th><td><span class="badge {{ $database['ok'] ? 'b-ok' : 'b-danger' }}">{{ $database['ok'] ? 'Connected' : 'Failed' }}</span></td></tr>
                <tr><th>Tables</th><td class="tnum">{{ number_format($database['tables']) }}</td></tr>
                <tr><th>Database Size</th><td>{{ $database['database_size'] ?? 'n/a' }}</td></tr>
                <tr><th>Version</th><td class="sub">{{ \Illuminate\Support\Str::limit($database['version'], 110) }}</td></tr>
            </tbody>
        </table></div>
    </section>
</div>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Catalog Completeness</h2><a class="btn btn-ghost" href="/admin/products">Products</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Metric</th><th class="num">Count</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>Products</td><td class="num tnum">{{ number_format($catalog['products']) }}</td><td><span class="badge b-info">canonical</span></td></tr>
                <tr><td>Search Documents</td><td class="num tnum">{{ number_format($catalog['search_documents']) }}</td><td><span class="badge {{ $catalog['search_documents'] >= max(1, $catalog['products'] - 5) ? 'b-ok' : 'b-warn' }}">indexed</span></td></tr>
                <tr><td>Marketplace Searchable</td><td class="num tnum">{{ number_format($catalog['marketplace_searchable']) }}</td><td><span class="badge b-info">regional catalog</span></td></tr>
                <tr><td>Public Products</td><td class="num tnum">{{ number_format($catalog['public_products']) }}</td><td><span class="badge b-warn">SEO review gate</span></td></tr>
                <tr><td>Facet Values</td><td class="num tnum">{{ number_format($catalog['facet_values']) }}</td><td><span class="badge b-info">filters</span></td></tr>
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Media Coverage</h2><a class="btn btn-ghost" href="/admin/media">Media</a></div>
        <div style="padding:16px;display:grid;gap:14px">
            <div>
                <div class="sub">Active image coverage</div>
                <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden"><div style="height:10px;width:{{ min(100, $catalog['image_coverage_percent']) }}%;background:#0891b2"></div></div>
                <div class="sub">{{ $catalog['image_coverage_percent'] }}% active image rows</div>
            </div>
            <div>
                <div class="sub">Licensed real image coverage</div>
                <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden"><div style="height:10px;width:{{ min(100, $catalog['licensed_image_percent']) }}%;background:#d97706"></div></div>
                <div class="sub">{{ $catalog['licensed_image_percent'] }}% approved source images</div>
            </div>
            <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                <div class="kpi"><div class="t">Active</div><div class="v tnum">{{ number_format($catalog['active_images']) }}</div><div class="s">image rows</div></div>
                <div class="kpi"><div class="t">Placeholder</div><div class="v tnum">{{ number_format($catalog['placeholder_images']) }}</div><div class="s">needs licensed feed</div></div>
                <div class="kpi"><div class="t">Candidates</div><div class="v tnum">{{ number_format($catalog['image_candidates']) }}</div><div class="s">review queue</div></div>
            </div>
        </div>
    </section>
</div>

<div class="grid dashboard-split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Queue and Imports</h2><span class="badge {{ $queue['ok'] ? 'b-ok' : 'b-danger' }}">{{ $queue['connection'] }}</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Metric</th><th class="num">Count</th></tr></thead>
            <tbody>
                <tr><td>Pending Jobs</td><td class="num tnum">{{ number_format($queue['pending_jobs']) }}</td></tr>
                <tr><td>Failed Jobs</td><td class="num tnum">{{ number_format($queue['failed_jobs']) }}</td></tr>
                <tr><td>Catalog Rebuild Jobs</td><td class="num tnum">{{ number_format($queue['catalog_rebuild_jobs']) }}</td></tr>
                <tr><td>Queued Rebuilds</td><td class="num tnum">{{ number_format($queue['queued_rebuilds']) }}</td></tr>
                <tr><td>Import Batches</td><td class="num tnum">{{ number_format($imports['batches']) }}</td></tr>
                <tr><td>Running Imports</td><td class="num tnum">{{ number_format($imports['running_batches']) }}</td></tr>
                <tr><td>Import Errors</td><td class="num tnum">{{ number_format($imports['import_errors']) }}</td></tr>
                <tr><td>BOM Imports</td><td class="num tnum">{{ number_format($imports['bom_imports']) }}</td></tr>
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Cache, Redis and Storage</h2><span class="sub">safe runtime checks</span></div>
        <div class="scroll-x"><table class="tbl">
            <tbody>
                <tr><th>Cache</th><td><span class="badge {{ $cache['ok'] ? 'b-ok' : 'b-danger' }}">{{ $cache['driver'] }}</span></td></tr>
                <tr><th>Redis</th><td><span class="badge {{ $badge($redis['ok']) }}">{{ $redis['message'] }}</span></td></tr>
                <tr><th>Disk Used</th><td>{{ $storage['used_percent'] ?? 'n/a' }}%</td></tr>
                <tr><th>Disk Free</th><td>{{ $fmt($storage['free_bytes']) }} of {{ $fmt($storage['total_bytes']) }}</td></tr>
                @foreach($storage['checks'] as $name => $check)
                    <tr><th>{{ str_replace('_', ' ', ucfirst($name)) }}</th><td><span class="badge {{ $check['exists'] && $check['writable'] ? 'b-ok' : 'b-danger' }}">{{ $check['exists'] ? 'exists' : 'missing' }} / {{ $check['writable'] ? 'writable' : 'not writable' }}</span></td></tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
</div>

@endsection
