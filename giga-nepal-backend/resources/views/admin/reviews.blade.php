@extends('admin.layout')
@section('title','Product Reviews')
@section('crumb','Review moderation')
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
                @foreach (['pending','approved','rejected','all'] as $s)<option value="{{ $s }}" @selected($statusFilter===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Product</th><th>Reviewer</th><th class="num">Rating</th><th>Review</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($reviews as $r)
            <tr>
                <td><strong>{{ $r->product_name }}</strong><div class="sub"><a href="/products/{{ $r->product_slug }}" target="_blank">/products/{{ $r->product_slug }}</a></div></td>
                <td>{{ $r->reviewer }}<div class="sub">{{ $r->reviewer_email }}@if($r->order_id) · <span class="badge b-info">verified purchase</span>@endif</div></td>
                <td class="num tnum">{{ $r->rating }}/5</td>
                <td style="max-width:340px">@if($r->title)<strong>{{ $r->title }}</strong><br>@endif{{ \Illuminate\Support\Str::limit($r->body, 180) }}</td>
                <td><span class="badge {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                <td class="sub">{{ $r->created_at }}</td>
                <td>
                    <form method="post" action="/admin/reviews/{{ $r->id }}/moderate" style="display:flex;gap:6px">@csrf
                        @if($r->status !== 'approved')<button class="btn btn-primary" name="status" value="approved" type="submit">Approve</button>@endif
                        @if($r->status !== 'rejected')<button class="btn btn-ghost" name="status" value="rejected" type="submit">Reject</button>@endif
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><h3>No reviews {{ $statusFilter !== 'all' ? "with status {$statusFilter}" : 'yet' }}</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($reviews->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $reviews->links() }}</div>
    @endif
</div>

@endsection
