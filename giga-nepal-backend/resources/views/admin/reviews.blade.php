@extends('admin.layout')
@section('title','Product Reviews')
@section('crumb','Review moderation queue')
@section('content')

@php $badge = fn($s) => $s === 'approved' ? 'b-ok' : ($s === 'pending' ? 'b-warn' : 'b-muted'); @endphp

<div class="grid kpis">
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">awaiting moderation</div></div>
    <div class="kpi"><div class="t">Approved</div><div class="v tnum">{{ number_format($stats['approved']) }}</div><div class="s">public</div></div>
    <div class="kpi"><div class="t">Rejected</div><div class="v tnum">{{ number_format($stats['rejected']) }}</div><div class="s">hidden</div></div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Reviews</h2>
        <form method="get" action="/admin/reviews" style="display:flex;gap:8px">
            <select class="control" name="status" style="min-height:34px">
                @foreach (['pending','approved','rejected','hidden','all'] as $s)<option value="{{ $s }}" @selected($statusFilter===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Product</th><th>Reviewer</th><th class="num">Rating</th><th>Review</th><th>Status</th><th>Moderate</th></tr></thead>
        <tbody>
        @forelse ($reviews as $r)
            <tr>
                <td><strong>{{ $r->product_name }}</strong><div class="sub"><a href="/admin/products/{{ $r->product_id }}">admin</a> · <a href="/products/{{ $r->product_slug }}" target="_blank">public</a></div></td>
                <td>{{ $r->reviewer_name ?? '—' }}<div class="sub">{{ $r->reviewer_email ?? '' }}@if($r->is_verified_buyer) · <span class="badge b-info">verified buyer</span>@endif</div></td>
                <td class="num tnum">{{ $r->rating }}/5</td>
                <td style="max-width:320px">@if($r->title)<strong>{{ $r->title }}</strong><br>@endif{{ \Illuminate\Support\Str::limit($r->body, 160) }}@if($r->use_case)<div class="sub">Use case: {{ $r->use_case }}</div>@endif</td>
                <td><span class="badge {{ $badge($r->status) }}">{{ $r->status }}</span><div class="sub">{{ $r->created_at }}</div></td>
                <td style="min-width:280px">
                    <form method="post" action="/admin/products/{{ $r->product_id }}/reviews/{{ $r->id }}" style="display:grid;grid-template-columns:120px 1fr auto;gap:6px">@csrf
                        <select class="control" name="status" style="min-height:34px">
                            @foreach (['pending','approved','rejected','hidden'] as $s)<option value="{{ $s }}" @selected($r->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                        <input class="control" name="moderation_note" maxlength="1000" placeholder="Note" style="min-height:34px">
                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No reviews {{ $statusFilter !== 'all' ? "with status {$statusFilter}" : 'yet' }}</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($reviews->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $reviews->links() }}</div>
    @endif
</div>

<div class="note" style="margin-top:16px">Moderation uses the per-product review action (same as the product detail page). Customer submissions arrive via <span class="mono">POST /api/v1/products/{id}/reviews</span> and always start as <em>pending</em>.</div>

@endsection
