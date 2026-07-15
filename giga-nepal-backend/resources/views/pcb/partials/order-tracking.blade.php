{{-- Order Tracking Timeline — expects $order (PcbOrder) --}}
@php
    $milestones = $order->milestones ?? [];
    $statusIcon = fn($s) => match($s) {
        'done' => '✓', 'current' => '●', default => '○'
    };
    $statusColor = fn($s) => match($s) {
        'done' => 'var(--cyan)', 'current' => 'var(--gold)', default => 'var(--line)'
    };
@endphp

<div class="card" style="margin-bottom:16px">
    <div class="card-head">
        <div><h2>Order tracking</h2><div class="muted" style="font-size:.78rem;font-family:ui-monospace,monospace">{{ $order->order_number }}</div></div>
        <span class="badge b-info">{{ str_replace('_',' ',$order->status) }}</span>
    </div>
    <div class="card-body">
        <div class="spec-list" style="margin-bottom:16px">
            <div><small>Total</small><span style="font-weight:700">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</span></div>
            <div><small>Payment</small><span style="font-weight:700">{{ ucfirst($order->payment_status) }}</span></div>
            @if($order->estimated_ship_date)<div><small>Est. ship date</small><span style="font-weight:700">{{ $order->estimated_ship_date->format('M j, Y') }}</span></div>@endif
            @if($order->tracking_number)<div><small>Tracking</small><span style="font-weight:700;color:var(--cyan)">{{ $order->tracking_carrier }} {{ $order->tracking_number }}</span></div>@endif
        </div>

        @if($milestones)
            <div style="display:grid;gap:0">
                @foreach($milestones as $i => $ms)
                    <div style="display:grid;grid-template-columns:24px 1fr;gap:12px;padding-bottom:{{ $loop->last ? '0' : '14px' }}">
                        <div style="display:flex;flex-direction:column;align-items:center">
                            <span style="width:22px;height:22px;border-radius:50%;border:2px solid {{ $statusColor($ms['status']) }};display:grid;place-items:center;font-size:.7rem;font-weight:900;color:{{ $statusColor($ms['status']) }};background:{{ $ms['status'] === 'done' ? 'rgba(40,216,251,.1)' : ($ms['status'] === 'current' ? 'rgba(249,189,44,.1)' : 'transparent') }}">{{ $statusIcon($ms['status']) }}</span>
                            @if(!$loop->last)<div style="width:1px;flex:1;background:{{ $ms['status'] === 'done' ? 'var(--cyan)' : 'var(--line)' }};margin:3px 0"></div>@endif
                        </div>
                        <div>
                            <div style="font-weight:{{ $ms['status'] !== 'pending' ? '700' : '400' }};color:{{ $ms['status'] !== 'pending' ? 'var(--on)' : 'var(--faint)' }};font-size:.88rem">{{ $ms['label'] }}</div>
                            @if($ms['date'])<time style="font-size:.74rem;color:var(--faint)">{{ \Carbon\Carbon::parse($ms['date'])->format('M j, Y H:i') }}</time>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
