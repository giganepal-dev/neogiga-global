@extends('frontend.layout')
@section('title','Track Your Order — NeoGiga')
@section('content')
<section style="padding:40px 0 64px"><div class="wrap" style="max-width:700px">
<h1 style="margin:0 0 8px">Track Your Order</h1>
<p style="color:var(--muted);margin:0 0 24px">Enter your order number and email to view status and tracking.</p>

<form method="post" action="/track-order" class="card" style="padding:24px;margin-bottom:24px">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Order Number *</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--s1);color:var(--on)" name="order_number" value="{{ $lookup ?? '' }}" required placeholder="e.g. ORD-2026-00001"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Email</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--s1);color:var(--on)" type="email" name="email" placeholder="Order email"></div>
    </div>
    <button class="btn btn-primary" type="submit" style="margin-top:12px">Track Order</button>
</form>

@if(isset($error))<div class="card" style="padding:16px;border-color:rgba(239,68,68,.3);margin-bottom:16px"><p style="color:#ef4444;margin:0">{{ $error }}</p></div>@endif

@if($order)
<div class="card" style="padding:24px">
    <h2 style="margin:0 0 16px;font-size:1.1rem">Order {{ $order->order_number }}</h2>
    <div style="margin-bottom:20px">
        <div style="display:flex;gap:12px;align-items:center">
            <span class="badge b-info">{{ $order->status ?? 'pending' }}</span>
            <span style="font-size:.82rem;color:var(--faint)">Placed: {{ $order->created_at ?? '—' }}</span>
        </div>
    </div>

    @php $statuses = ['pending','confirmed','processing','shipped','delivered']; $current = array_search($order->status, $statuses); @endphp
    <div style="margin-bottom:20px">
        @foreach($statuses as $i => $s)
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;{{ $i <= $current ? '' : 'opacity:.4' }}">
                <div style="width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-size:.8rem;{{ $i === $current ? 'background:var(--cyan);color:#003640' : ($i < $current ? 'background:rgba(16,185,129,.15);color:#34d399' : 'background:rgba(255,255,255,.05);color:var(--muted)') }};flex:none">{{ $i + 1 }}</div>
                <span style="font-weight:{{ $i === $current ? '700' : '400' }}">{{ ucfirst($s) }}</span>
            </div>
        @endforeach
    </div>

    @if($order->tracking_number)
        <div style="background:rgba(40,216,251,.06);border:1px solid rgba(40,216,251,.2);border-radius:10px;padding:14px;margin-bottom:16px">
            <div style="font-size:.74rem;color:var(--faint);text-transform:uppercase;letter-spacing:.06em">Tracking Number</div>
            <div style="font-family:monospace;font-size:1rem;color:var(--cyan);margin-top:4px">{{ $order->tracking_number }}</div>
        </div>
    @else
        <p style="color:var(--muted);font-size:.86rem">Tracking information will be available once your order ships.</p>
    @endif

    @auth
        <a href="/en/dashboard" class="btn btn-ghost">View All Orders</a>
    @endif
</div>
@endif
</div></section>
@endsection
