@extends('seller.layout')
@section('title', 'My Orders')
@section('content')

<div class="card">
    <div class="card-h"><h2>My Orders</h2>@if($orders)<span class="badge b-muted">{{ number_format($orders->total()) }} orders</span>@endif</div>
    @if (is_null($orders))
        <div class="empty"><h3>Order splitting is being enabled</h3><p>Vendor order records are not provisioned on this environment yet. Your orders will appear here automatically once enabled.</p></div>
    @else
        <form class="filters" method="get">
            <select class="control" name="status"><option value="">All statuses</option>@foreach(['pending','processing','shipped','fulfilled','delivered','cancelled'] as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
            <button class="btn" type="submit"><x-icon name="filter" :size="16" /> Filter</button>
        </form>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Vendor order</th><th>Status</th><th class="num">Subtotal</th><th class="num">Your net</th><th>Placed</th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td class="mono">#{{ $o->id }}@if(isset($o->order_id))<div class="sub">order {{ $o->order_id }}</div>@endif</td>
                    <td><span class="badge {{ in_array($o->status, ['fulfilled','delivered','shipped']) ? 'b-ok' : ($o->status === 'cancelled' ? 'b-bad' : 'b-warn') }}">{{ ucfirst($o->status) }}</span></td>
                    <td class="num tnum">{{ number_format($o->subtotal ?? 0, 2) }}</td>
                    <td class="num tnum">{{ number_format($o->vendor_net_total ?? 0, 2) }}</td>
                    <td class="sub">{{ \Illuminate\Support\Carbon::parse($o->created_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty"><h3>No orders yet</h3><p>Orders containing your products will appear here.</p></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if ($orders->hasPages())<div style="padding:12px 16px">{{ $orders->links() }}</div>@endif
    @endif
</div>

@endsection
