@extends('admin.layout')
@section('title','Sellers')
@section('crumb','Network / Seller Management')
@section('page_actions')
<details class="modal">
    <summary class="btn btn-primary">Add Seller</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Create Seller</h3><span class="badge b-info">KYC-ready</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/vendors">@csrf
            <div class="form-grid">
                <div class="field"><label>Business name</label><input class="control" name="name" required></div>
                <div class="field"><label>Slug</label><input class="control mono" name="slug"></div>
                <div class="field"><label>Email</label><input class="control" type="email" name="email"></div>
                <div class="field"><label>Phone</label><input class="control" name="phone"></div>
                <div class="field"><label>Seller type</label><select class="control" name="type"><option value="company">Company</option><option value="individual">Individual</option><option value="manufacturer">Manufacturer</option><option value="distributor">Distributor</option></select></div>
                <div class="field"><label>Status</label><select class="control" name="status"><option>pending</option><option>active</option><option>suspended</option><option>rejected</option></select></div>
                <div class="field"><label>Country</label><select class="control" name="country_id"><option value="">Global</option>@foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach</select></div>
                <div class="field"><label>Commission %</label><input class="control" type="number" step="0.01" name="commission_rate"></div>
            </div>
            <div class="field"><label>Website</label><input class="control" name="website"></div>
            <div class="field"><label>Description</label><textarea class="control" name="description"></textarea></div>
            <div class="form-grid"><div class="field"><label>Country visibility</label><input class="control" name="country_visibility"></div><div class="field"><label>Marketplace visibility</label><input class="control" name="marketplace_visibility"></div></div>
            <div class="field"><label>Settlement info placeholder</label><textarea class="control" name="settlement_note"></textarea></div>
            <button class="btn btn-primary" type="submit">Create Seller</button>
        </form>
    </div>
</details>
@endsection
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Sellers</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">to review</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v tnum">{{ number_format($stats['approved']) }}</div><div class="s">approved network</div></div>
    <div class="kpi"><div class="t">KYC pending</div><div class="v tnum">{{ number_format($stats['documentsPending']) }}</div><div class="s">documents</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Seller Management</h2><div class="sub">{{ number_format($vendors->total()) }} filtered sellers</div></div><span class="badge b-info">profiles + status</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name, email, slug">
        <select class="control" name="status"><option value="">All status</option>@foreach(['pending','active','suspended','rejected'] as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
        <select class="control" name="type"><option value="">All types</option>@foreach(['individual','company','manufacturer','distributor'] as $t)<option value="{{ $t }}" @selected($filters['type']===$t)>{{ ucfirst($t) }}</option>@endforeach</select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Seller</th><th>Type</th><th>Contact</th><th>Performance</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($vendors as $v)
            @php $s = $v->status ?? 'pending'; @endphp
            <tr>
                <td><strong>{{ $v->name ?? ('Seller #'.$v->id) }}</strong><div class="sub mono">{{ $v->slug ?? 'no-slug' }}</div></td>
                <td>{{ str_replace('_',' ',ucfirst($v->type ?? $v->business_type ?? 'seller')) }}</td>
                <td>{{ $v->email ?: '—' }}<div class="sub">{{ $v->phone ?: $v->website }}</div></td>
                <td><span class="tnum">{{ number_format((float) ($v->rating_average ?? 0), 1) }}</span> rating<div class="sub">sales {{ number_format((float) ($v->total_sales ?? 0), 2) }}</div></td>
                <td><span class="badge {{ $s==='active'?'b-ok':($s==='pending'?'b-warn':'b-muted') }}">{{ ucfirst($s) }}</span></td>
                <td class="actions">
                    <details class="modal"><summary class="btn btn-ghost">View/Edit</summary>
                        <div class="modal-panel"><div class="modal-h"><h3>{{ $v->name }}</h3><span class="badge b-info">seller profile</span></div>
                            <form class="modal-b form-stack" method="post" action="/admin/vendors">@csrf
                                <input type="hidden" name="id" value="{{ $v->id }}">
                                <div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $v->name }}" required></div><div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $v->slug }}"></div><div class="field"><label>Email</label><input class="control" name="email" value="{{ $v->email }}"></div><div class="field"><label>Phone</label><input class="control" name="phone" value="{{ $v->phone }}"></div><div class="field"><label>Type</label><select class="control" name="type">@foreach(['individual','company','manufacturer','distributor'] as $type)<option value="{{ $type }}" @selected($v->type===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div><div class="field"><label>Status</label><select class="control" name="status">@foreach(['pending','active','suspended','rejected'] as $status)<option value="{{ $status }}" @selected($v->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div></div>
                                <div class="field"><label>Description</label><textarea class="control" name="description">{{ $v->description }}</textarea></div>
                                <div class="form-grid"><div class="field"><label>Tax number</label><input class="control" name="tax_number" value="{{ $v->tax_number }}"></div><div class="field"><label>Registration number</label><input class="control" name="registration_number" value="{{ $v->registration_number }}"></div><div class="field"><label>Commission %</label><input class="control" type="number" step="0.01" name="commission_rate"></div><div class="field"><label>Settlement info</label><input class="control" name="settlement_note" placeholder="placeholder"></div></div>
                                <button class="btn btn-primary" type="submit">Save Seller</button>
                                <div class="note">KYC documents, seller products, performance, status timeline, settlement and marketplace visibility are shown as operational sections; deeper editors continue in the next seller slice.</div>
                            </form>
                        </div>
                    </details>
                    <form method="post" action="/admin/vendors/{{ $v->id }}/status">@csrf<input type="hidden" name="status" value="active"><button class="btn btn-ghost" type="submit">Approve</button></form>
                    <details class="modal"><summary class="btn btn-ghost danger">Reject/Suspend</summary><div class="modal-panel"><div class="modal-h"><h3>Status Change</h3></div><form class="modal-b form-stack" method="post" action="/admin/vendors/{{ $v->id }}/status">@csrf<select class="control" name="status"><option value="rejected">Reject</option><option value="suspended">Suspend</option><option value="pending">Back to pending</option></select><textarea class="control" name="note" placeholder="Reason"></textarea><button class="btn btn-primary" type="submit">Save Status</button></form></div></details>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No sellers found</h3><p>Create a seller or approve a seller application.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($vendors->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $vendors->links() }}</div>@endif
</section>

<div class="grid split stack-gap">
    <section class="card"><div class="card-h"><h2>KYC Documents</h2><span class="badge b-warn">review queue</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Vendor</th><th>Name</th><th>Type</th><th>Status</th></tr></thead><tbody>@forelse($recentDocuments as $d)<tr><td>#{{ $d->vendor_id }}</td><td>{{ $d->name }}</td><td>{{ $d->type }}</td><td><span class="badge {{ $d->status==='approved'?'b-ok':'b-warn' }}">{{ $d->status }}</span></td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No KYC documents</h3></div></td></tr>@endforelse</tbody></table></div></section>
    <section class="card"><div class="card-h"><h2>Seller Products</h2><span class="badge b-info">latest submissions</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Vendor</th><th>Product</th><th>SKU</th><th>Status</th></tr></thead><tbody>@forelse($recentProducts as $p)<tr><td>#{{ $p->vendor_id }}</td><td>{{ $p->name }}</td><td class="mono">{{ $p->sku }}</td><td><span class="badge {{ $p->status==='approved'?'b-ok':'b-muted' }}">{{ $p->status }}</span></td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No seller products</h3></div></td></tr>@endforelse</tbody></table></div></section>
</div>

@endsection
