@extends('admin.layout')
@section('title','Tax, Tariff & Import Duties')
@section('crumb','Regional Commerce / Tax & Tariff')
@section('content')

<style>
.tax-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:768px){.tax-grid{grid-template-columns:1fr}}
</style>

<div class="tax-grid">
    {{-- Tax Zones --}}
    <div class="card"><div class="card-h"><h2>Tax Zones</h2><span class="badge b-info">{{ $zones->count() }} zones</span></div><div class="card-b">
        <table><thead><tr><th>Name</th><th>Rate</th><th>Type</th><th>Active</th><th></th></tr></thead>
        <tbody>@foreach($zones as $z)<tr><td>{{ $z->name }}<br><small style="color:var(--muted)">{{ $z->code }}</small></td><td>{{ $z->tax_rate }}%</td><td>{{ $z->is_compound ? 'Compound' : 'Single' }} · {{ $z->is_inclusive ? 'Inclusive' : 'Exclusive' }}</td><td><span class="badge {{ $z->is_active ? 'b-ok' : 'b-muted' }}">{{ $z->is_active ? 'Active' : 'Inactive' }}</span></td><td><form method="post" action="/admin/tax/zones/{{ $z->id }}/toggle">@csrf<button class="btn btn-ghost btn-sm">{{ $z->is_active ? 'Deactivate' : 'Activate' }}</button></form></td></tr>@endforeach</tbody></table>
        <form method="post" action="/admin/tax/zones" style="margin-top:12px;display:grid;gap:8px">@csrf
            <select name="marketplace_id"><option value="">— Marketplace —</option>@foreach($marketplaces as $m)<option value="{{ $m->id }}">{{ $m->name }} ({{ $m->country_iso2 }})</option>@endforeach</select>
            <input name="name" placeholder="Zone name (e.g. Nepal VAT)" required>
            <input name="code" placeholder="Code (e.g. NP-VAT)" required>
            <div style="display:flex;gap:8px"><input type="number" name="tax_rate" step="0.01" placeholder="Rate %" required style="flex:1"><input type="number" name="priority" value="10" style="width:80px"></div>
            <div style="display:flex;gap:8px;align-items:center"><label><input type="checkbox" name="is_compound"> Compound</label><label><input type="checkbox" name="is_inclusive"> Tax-inclusive</label><label><input type="checkbox" name="is_active" checked> Active</label></div>
            <button class="btn btn-primary">Add Tax Zone</button>
        </form>
    </div></div>

    {{-- Import Duties --}}
    <div class="card"><div class="card-h"><h2>Import Duties</h2><span class="badge b-info">{{ $duties->count() }} rules</span></div><div class="card-b">
        <table><thead><tr><th>HS Code</th><th>Country</th><th>Rate</th><th>Active</th></tr></thead>
        <tbody>@foreach($duties as $d)<tr><td class="mono">{{ $d->hs_code }}</td><td>{{ $d->origin_country ?? 'Any' }}</td><td>{{ $d->duty_rate }}% ({{ $d->duty_type }})</td><td><span class="badge {{ $d->is_active ? 'b-ok' : 'b-muted' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td></tr>@endforeach</tbody></table>
        <form method="post" action="/admin/tax/duties" style="margin-top:12px;display:grid;gap:8px">@csrf
            <select name="country_id"><option value="">— Country —</option>@foreach($countries as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->iso_code_2 }})</option>@endforeach</select>
            <select name="marketplace_id"><option value="">— Marketplace —</option>@foreach($marketplaces as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
            <input name="hs_code" placeholder="HS Code (e.g. 8542.31)" required>
            <div style="display:flex;gap:8px"><input type="number" name="duty_rate" step="0.01" placeholder="Rate %" required style="flex:1"><select name="duty_type"><option>ad_valorem</option><option>specific</option><option>compound</option><option>exempt</option></select></div>
            <input name="origin_country" placeholder="Origin country ISO (optional)">
            <label><input type="checkbox" name="is_active" checked> Active</label>
            <button class="btn btn-primary">Add Duty Rule</button>
        </form>
    </div></div>

    {{-- Source Registry --}}
    <div class="card"><div class="card-h"><h2>Official Sources</h2><span class="badge b-info">{{ $sources->count() }} sources</span></div><div class="card-b">
        <table><thead><tr><th>Country</th><th>Source</th><th>Type</th><th>Active</th></tr></thead>
        <tbody>@foreach($sources as $s)<tr><td>{{ $s->country_code }}</td><td>{{ $s->source_name }}<br><small style="color:var(--muted)">{{ $s->official_domain }}</small></td><td>{{ $s->source_type }}</td><td><span class="badge {{ $s->active ? 'b-ok' : 'b-muted' }}">{{ $s->active ? 'Active' : 'Inactive' }}</span></td></tr>@endforeach</tbody></table>
    </div></div>
</div>
@endsection
