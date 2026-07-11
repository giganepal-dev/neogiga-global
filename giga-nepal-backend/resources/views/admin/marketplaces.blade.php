@extends('admin.layout')
@section('title','Marketplaces')
@section('crumb','Regional editions & host resolution')
@section('content')

@php $f = $filters ?? []; @endphp
<style>
    .mfilter{display:flex;flex-wrap:wrap;gap:10px;align-items:end}
    .mfilter .fld{display:grid;gap:4px}.mfilter label{font-size:.72rem;font-weight:700;color:var(--slate)}
    .mfilter input,.mfilter select{border:1px solid var(--line);border-radius:8px;padding:8px 10px;font:inherit;min-width:120px}
    .bulkbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px}
</style>

<div class="card" style="margin-bottom:16px"><div class="card-b">
    <form class="mfilter" method="get" action="/admin/marketplaces">
        <div class="fld"><label>Search</label><input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="name, code, domain, country"></div>
        <div class="fld"><label>Status</label><select name="status"><option value="">Any</option><option value="active" @selected(($f['status']??'')==='active')>Active</option><option value="inactive" @selected(($f['status']??'')==='inactive')>Inactive</option></select></div>
        <div class="fld"><label>Visibility</label><select name="visibility"><option value="">Any</option><option value="visible" @selected(($f['visibility']??'')==='visible')>Visible</option><option value="hidden" @selected(($f['visibility']??'')==='hidden')>Hidden</option></select></div>
        <div class="fld"><label>Domain</label><select name="domain_status"><option value="">Any</option><option value="verified" @selected(($f['domain_status']??'')==='verified')>Verified</option><option value="pending" @selected(($f['domain_status']??'')==='pending')>Pending</option></select></div>
        <div class="fld"><label>SEO</label><select name="seo_status"><option value="">Any</option><option value="complete" @selected(($f['seo_status']??'')==='complete')>Complete</option><option value="incomplete" @selected(($f['seo_status']??'')==='incomplete')>Incomplete</option></select></div>
        <div class="fld"><label>Country</label><select name="country_id"><option value="">Any</option>@foreach(($countries ?? collect()) as $c)<option value="{{ $c->id }}" @selected((string)($f['country_id']??'')===(string)$c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="fld"><label>Currency</label><select name="currency_id"><option value="">Any</option>@foreach(($currencies ?? collect()) as $cur)<option value="{{ $cur->id }}" @selected((string)($f['currency_id']??'')===(string)$cur->id)>{{ $cur->code }}</option>@endforeach</select></div>
        <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="missing_domain" value="1" @checked(!empty($f['missing_domain']))> Missing domain</label>
        <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="missing_seo" value="1" @checked(!empty($f['missing_seo']))> Missing SEO</label>
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn" href="/admin/marketplaces">Reset</a>
    </form>
</div></div>

<form method="post" action="/admin/marketplaces/bulk" onsubmit="return document.querySelectorAll('.mrow:checked').length>0 || (alert('Select at least one marketplace.'),false)">@csrf
<div class="card">
    <div class="card-h"><div><h2>Marketplaces</h2><div class="sub">{{ number_format($marketplaces->count()) }} shown</div></div></div>
    <div class="card-b bulkbar">
        <select name="action" required>
            <option value="">Bulk action…</option>
            <option value="enable">Enable selected</option>
            <option value="disable">Disable selected</option>
            <option value="set_visible">Set visible</option>
            <option value="set_hidden">Set hidden</option>
            <option value="generate_missing_domains">Generate missing domains</option>
            <option value="generate_default_seo">Generate default SEO</option>
        </select>
        <input type="text" name="reason" placeholder="Reason (for disable)">
        <button class="btn btn-primary" type="submit">Apply to selected</button>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th><input type="checkbox" id="selall"></th><th>Name</th><th>Code</th><th>Country</th><th>Currency</th><th>Domain</th><th>Mode</th><th>Visibility</th><th>SEO</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($marketplaces as $m)
                <tr>
                    <td><input type="checkbox" class="mrow" name="ids[]" value="{{ $m->id }}"></td>
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
                <tr><td colspan="11"><div class="empty"><h3>No marketplaces match</h3><p>Adjust the filters or run the MarketplaceSeeder.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</form>
<script>
(function(){var s=document.getElementById('selall');if(s)s.addEventListener('change',function(){document.querySelectorAll('.mrow').forEach(function(c){c.checked=s.checked;});});})();
</script>

@endsection
