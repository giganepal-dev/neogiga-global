{{-- Order Tracking Modal — show tracking timeline, shipments, carrier info --}}
@props(['order'])
<div id="tracking-modal-{{ $order->id }}" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
<div class="modal-card" style="background:var(--s1);border:1px solid var(--line);border-radius:14px;max-width:560px;width:calc(100% - 32px);max-height:80vh;overflow-y:auto;padding:24px;color:var(--on)" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h2 style="margin:0;font-size:1.2rem">Order Tracking — {{ $order->order_number ?? '#' . $order->id }}</h2>
        <button onclick="document.getElementById('tracking-modal-{{ $order->id }}').style.display='none'" style="background:none;border:none;color:var(--muted);font-size:1.5rem;cursor:pointer">&times;</button>
    </div>

    {{-- Status Timeline --}}
    @php
    $statuses = ['pending','confirmed','processing','shipped','delivered'];
    $currentIdx = array_search($order->status, $statuses);
    $icons = ['pending'=>'⏳','confirmed'=>'✅','processing'=>'🏭','shipped'=>'🚚','delivered'=>'📦'];
    @endphp

    <div class="tracking-timeline" style="margin-bottom:24px">
        @foreach($statuses as $i => $s)
            @php $done = $i <= $currentIdx; $active = $i === $currentIdx; @endphp
            <div style="display:flex;align-items:flex-start;gap:12px;padding:8px 0;{{ $done ? '' : 'opacity:0.4' }}">
                <div style="width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-size:.9rem;{{ $active ? 'background:var(--cyan);color:#003640' : ($done ? 'background:rgba(16,185,129,.15);color:#34d399' : 'background:rgba(255,255,255,.05);color:var(--muted)') }};flex:none">
                    {{ $icons[$s] }}
                </div>
                <div>
                    <div style="font-weight:700;font-size:.9rem">{{ ucfirst($s) }}</div>
                    @if($done && $order->{match($s){'shipped'=>'shipped_at','delivered'=>'delivered_at',default=>'created_at'}})
                        <div style="font-size:.72rem;color:var(--faint)">{{ \Carbon\Carbon::parse($order->{match($s){'shipped'=>'shipped_at','delivered'=>'delivered_at',default=>'created_at'}})->format('M j, Y g:i A') }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tracking Number --}}
    @if($order->tracking_number)
        <div style="background:rgba(40,216,251,.06);border:1px solid rgba(40,216,251,.15);border-radius:10px;padding:14px;margin-bottom:16px">
            <div style="font-size:.74rem;color:var(--faint);text-transform:uppercase;letter-spacing:.06em">Tracking Number</div>
            <div style="font-family:monospace;font-size:1rem;color:var(--cyan);margin-top:4px">{{ $order->tracking_number }}</div>
            @if($order->carrier)
                <div style="font-size:.78rem;color:var(--muted);margin-top:4px">Carrier: {{ $order->carrier }}</div>
            @endif
        </div>
    @endif

    {{-- Shipments --}}
    @php $shipments = $order->shipments ?? collect(); @endphp
    @if($shipments->isNotEmpty())
        <h3 style="font-size:.9rem;color:var(--muted);margin:0 0 12px">Shipments</h3>
        @foreach($shipments as $shipment)
            <div style="padding:10px 14px;border:1px solid var(--line);border-radius:8px;margin-bottom:8px;font-size:.82rem">
                <div style="display:flex;justify-content:space-between">
                    <strong>{{ $shipment->carrier ?? 'Standard' }}</strong>
                    <span class="badge b-info" style="font-size:.68rem">{{ $shipment->status ?? 'in transit' }}</span>
                </div>
                @if($shipment->tracking_url)
                    <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" style="color:var(--cyan);font-size:.76rem">Track shipment →</a>
                @endif
                @if($shipment->estimated_delivery)
                    <div style="font-size:.72rem;color:var(--faint);margin-top:4px">Est. delivery: {{ \Carbon\Carbon::parse($shipment->estimated_delivery)->format('M j, Y') }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if(!$order->tracking_number && $shipments->isEmpty())
        <div style="text-align:center;padding:20px;color:var(--muted)">Tracking information will appear once your order ships.</div>
    @endif

    <button onclick="document.getElementById('tracking-modal-{{ $order->id }}').style.display='none'" class="btn btn-ghost" style="width:100%;margin-top:12px">Close</button>
</div>
</div>
