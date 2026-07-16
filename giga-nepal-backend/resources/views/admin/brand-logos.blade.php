@extends('admin.layout')

@section('title', 'Brand logos')
@section('page_title', 'Brand logo management')
@section('breadcrumb', 'Catalog / Brand logos')

@section('content')
<div class="page-head">
    <div><h2>Brand logo management</h2><p>Only reviewed official-source logos can be published to the brand directory.</p></div>
    <div class="page-actions">
        @foreach(['discover_missing' => 'Discover missing', 'verify_pending' => 'Verify pending', 'regenerate_variants' => 'Regenerate variants', 'check_broken' => 'Check broken'] as $action => $label)
            <form method="post" action="{{ route('brand-logos.bulk') }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button class="btn btn-ghost" type="submit">{{ $label }}</button></form>
        @endforeach
        <a class="btn btn-ghost" href="{{ route('brand-logos.export-missing') }}">Export missing</a>
    </div>
</div>

<div class="note">Supplier-provided logos are never published automatically. Discovery and uploads create review candidates; approval preserves provenance and previous-logo history.</div>

<form class="filters" method="get">
    <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search brands">
    <select class="control" name="status"><option value="">All logo statuses</option>@foreach(['pending','verified','rejected','unavailable','manual_review'] as $status)<option value="{{ $status }}" @selected($filters['status']===$status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select>
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<div class="card">
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Brand</th><th>Current logo</th><th>Verification</th><th>Source</th><th>Products</th><th>Actions</th></tr></thead><tbody>
    @forelse($brands as $brand)
        @php($publishedLogo = $brand->verifiedLogoUrl())
        <tr>
            <td><strong>{{ $brand->name }}</strong><br><span class="mono" style="color:var(--muted)">{{ $brand->slug }}</span></td>
            <td>@if($publishedLogo)<img src="{{ $publishedLogo }}" alt="{{ $brand->logo_alt_text ?: $brand->name.' logo' }}" width="120" height="42" style="width:120px;height:42px;object-fit:contain;background:#fff;border:1px solid var(--line);border-radius:6px;padding:4px">@else<span class="badge b-muted">{{ strtoupper(substr($brand->name, 0, 1)) }}</span>@endif</td>
            <td><span class="badge {{ $brand->logo_verified ? 'b-ok' : ($brand->logo_status === 'manual_review' ? 'b-warn' : 'b-muted') }}">{{ $brand->logo_verified ? 'Verified' : str_replace('_', ' ', $brand->logo_status ?: 'pending') }}</span>@if($brand->logo_confidence !== null)<br><span style="color:var(--muted)">{{ number_format($brand->logo_confidence, 2) }} confidence</span>@endif</td>
            <td><span>{{ $brand->logo_source_domain ?: 'Not verified' }}</span><br><span style="color:var(--muted)">{{ $brand->logo_source_type ?: 'No source' }}</span></td>
            <td class="tnum">{{ number_format($brand->products_count) }}</td>
            <td><div class="actions">
                <form method="post" action="{{ route('brand-logos.discover', $brand) }}">@csrf<button class="btn btn-ghost" type="submit">Fetch official</button></form>
                <details class="modal"><summary class="btn btn-ghost">Upload</summary><div class="modal-panel"><div class="modal-h"><h3>Stage {{ $brand->name }} logo</h3><button class="btn btn-ghost" type="button" onclick="this.closest('details').removeAttribute('open')">Close</button></div><form class="modal-b form-stack" method="post" enctype="multipart/form-data" action="{{ route('brand-logos.upload', $brand) }}">@csrf<div class="field"><label>Official logo file</label><input class="control" type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif,image/avif,image/svg+xml" required></div><div class="field"><label>Official source URL</label><input class="control" name="source_url" type="url"></div><div class="field"><label>Review note</label><textarea class="control" name="review_note"></textarea></div><button class="btn btn-primary" type="submit">Stage for review</button></form></div></details>
                @foreach($brand->logoHistory->where('status','pending') as $history)
                    <form method="post" action="{{ route('brand-logos.approve', [$brand, $history]) }}">@csrf<button class="btn btn-primary" type="submit">Approve</button></form>
                    <form method="post" action="{{ route('brand-logos.reject', [$brand, $history]) }}">@csrf<button class="btn danger" type="submit">Reject</button></form>
                @endforeach
                <form method="post" action="{{ route('brand-logos.unavailable', $brand) }}">@csrf<button class="btn btn-ghost" type="submit">Unavailable</button></form>
                @if($brand->logo_path)<form method="post" action="{{ route('brand-logos.remove', $brand) }}">@csrf @method('delete')<button class="btn danger" type="submit">Remove</button></form>@endif
            </div></td>
        </tr>
    @empty
        <tr><td colspan="6"><div class="empty"><h3>No brands matched</h3><p>Clear the filters to view catalog brand identities.</p></div></td></tr>
    @endforelse
    </tbody></table></div>
</div>
<div style="margin-top:18px">{{ $brands->links() }}</div>
@endsection
