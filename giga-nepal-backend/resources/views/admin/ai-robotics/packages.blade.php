@extends('admin.layout')
@section('title', 'Institutional Packages')
@section('crumb', 'AI & Robotics / Institutional Packages')
@section('content')
<div class="page-head"><div><h2>Institutional Packages</h2></div><div class="page-actions"><a href="/admin/ai-robotics/packages/create" class="btn btn-primary">Add Package</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Packages ({{ $packages->total() }})</h2></div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Institution</th><th>Products</th><th>Price</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($packages as $p)<tr>
            <td style="font-weight:600">{{ $p->name }}</td>
            <td>{{ ucfirst($p->target_institution ?? '—') }}</td>
            <td class="num">{{ $p->products_count }}</td>
            <td>{{ $p->base_price ? $p->currency.' '.number_format($p->base_price,2) : '—' }}</td>
            <td><span class="badge {{ $p->is_active ? 'b-ok' : 'b-muted' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td><a href="/admin/ai-robotics/packages/{{ $p->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td>
        </tr>@empty<tr><td colspan="6" class="empty">No packages yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $packages->links() }}
</div>
@endsection
