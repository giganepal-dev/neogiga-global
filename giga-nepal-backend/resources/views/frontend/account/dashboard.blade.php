@extends('frontend.layout')
@section('title','My Account — NeoGiga')
@section('content')

<h1 style="margin-bottom:24px">My Account</h1>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px">
    <section class="card" style="padding:20px">
        <h2 style="margin:0 0 12px;font-size:1.1rem">Profile</h2>
        <p style="color:var(--soft);margin:0">{{ $user->name ?? 'Customer' }}</p>
        <p class="mono" style="color:var(--muted);font-size:.82rem;margin:4px 0 0">{{ $user->email }}</p>
    </section>

    <section class="card" style="padding:20px">
        <h2 style="margin:0 0 12px;font-size:1.1rem">Recent Orders</h2>
        @if ($orders->isEmpty())
            <p style="color:var(--muted)">No orders yet.</p>
        @else
            <div style="display:grid;gap:8px">
                @foreach ($orders->take(5) as $order)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)">
                        <span class="mono" style="font-size:.82rem">{{ $order->order_number ?? '#' . $order->id }}</span>
                        <span class="badge" style="font-size:.72rem">{{ $order->status ?? 'processing' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card" style="padding:20px">
        <h2 style="margin:0 0 12px;font-size:1.1rem">RFQ Requests</h2>
        @if ($rfqs->isEmpty())
            <p style="color:var(--muted)">No RFQ requests yet. <a href="/en/rfq" style="color:var(--cyan)">Create one</a>.</p>
        @else
            <div style="display:grid;gap:8px">
                @foreach ($rfqs->take(5) as $rfq)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)">
                        <span class="mono" style="font-size:.82rem">{{ $rfq->rfq_number ?? 'RFQ #' . $rfq->id }}</span>
                        <span class="badge" style="font-size:.72rem">{{ $rfq->status ?? 'pending' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<form method="post" action="/logout" style="margin-top:24px">
    @csrf
    <button class="btn btn-ghost" type="submit">Sign Out</button>
</form>
@endsection
