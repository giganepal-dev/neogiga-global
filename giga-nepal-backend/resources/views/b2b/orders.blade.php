@extends('b2b.layout')
@section('title','Orders')
@section('content')
<h1 style="margin:0 0 24px">Orders</h1>
@if($orders->isEmpty())
    <div class="card" style="text-align:center;padding:40px"><p style="color:var(--muted)">No orders yet.</p></div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($orders as $o)
                <tr>
                    <td class="mono" style="font-size:.78rem"><strong>{{ $o->order_number ?? '#'.$o->id }}</strong></td>
                    <td>{{ $o->customer_email ?? $o->customer_name ?? '—' }}</td>
                    <td>{{ $o->total ?? '$0.00' }}</td>
                    <td><span class="badge b-info">{{ $o->status ?? 'pending' }}</span></td>
                    <td style="font-size:.78rem;color:var(--faint)">{{ $o->created_at ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
@endif
@endsection
