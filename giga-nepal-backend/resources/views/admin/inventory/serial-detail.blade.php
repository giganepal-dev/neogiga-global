@extends('admin.layout')
@section('title', 'Serial Number Details')
@section('crumb', 'Inventory / Serials / ' . $record->serial_number)

@section('content')
<div class="page-head">
    <div>
        <h2>Serial {{ $record->serial_number }}</h2>
        <p style="color:var(--muted)">{{ $record->product_name ?? '—' }} @if($record->mpn) &middot; {{ $record->mpn }} @endif</p>
    </div>
    <div class="page-actions">
        <a href="/admin/inventory/serials" class="btn btn-ghost">Back to Serials</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
    <div>
        <div class="card">
            <div class="card-h"><h2>Serial Information</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <div class="sub">Serial Number</div>
                        <div style="font-weight:600;font-family:monospace">{{ $record->serial_number }}</div>
                    </div>
                    <div>
                        <div class="sub">Manufacturer Serial</div>
                        <div>{{ $record->manufacturer_serial ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Status</div>
                        <div>
                            @php
                                $badge = match($record->status) {
                                    'available' => 'b-ok',
                                    'sold' => 'b-muted',
                                    'returned' => 'b-info',
                                    'damaged' => 'b-danger',
                                    'lost' => 'b-danger',
                                    'in_repair' => 'b-warn',
                                    default => 'b-muted',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_',' ',$record->status)) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="sub">Batch</div>
                        <div>{{ $record->batch_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Warehouse</div>
                        <div>{{ $record->warehouse_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Variant</div>
                        <div>{{ $record->variant_name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Pricing</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <div class="sub">Purchase Cost</div>
                        <div style="font-weight:600">{{ $record->purchase_cost ? number_format($record->purchase_cost, 4) : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Sale Price</div>
                        <div style="font-weight:600">{{ $record->sale_price ? number_format($record->sale_price, 4) : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-h"><h2>Dates</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div>
                        <div class="sub">Manufacturing Date</div>
                        <div>{{ $record->manufacturing_date ? \Carbon\Carbon::parse($record->manufacturing_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Purchase Date</div>
                        <div>{{ $record->purchase_date ? \Carbon\Carbon::parse($record->purchase_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Sale Date</div>
                        <div>{{ $record->sale_date ? \Carbon\Carbon::parse($record->sale_date)->format('M d, Y') : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Warranty</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div>
                        <div class="sub">Status</div>
                        <div>
                            @php
                                $wBadge = match($record->warranty_status) {
                                    'active' => 'b-ok',
                                    'expired' => 'b-muted',
                                    'void' => 'b-danger',
                                    'claimed' => 'b-warn',
                                    default => 'b-muted',
                                };
                            @endphp
                            <span class="badge {{ $wBadge }}">{{ ucfirst($record->warranty_status ?? '—') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="sub">Start Date</div>
                        <div>{{ $record->warranty_start_date ? \Carbon\Carbon::parse($record->warranty_start_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">End Date</div>
                        @php $warrantyExpired = $record->warranty_end_date && \Carbon\Carbon::parse($record->warranty_end_date)->isPast(); @endphp
                        <div style="color:{{ $warrantyExpired ? 'var(--danger)' : 'var(--fg)' }}">
                            {{ $record->warranty_end_date ? \Carbon\Carbon::parse($record->warranty_end_date)->format('M d, Y') : '—' }}
                            @if($warrantyExpired) <span class="badge b-danger">EXPIRED</span> @endif
                        </div>
                    </div>
                    @if($record->warranty_notes)
                    <div>
                        <div class="sub">Notes</div>
                        <div style="color:var(--muted)">{{ $record->warranty_notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if(!empty($serviceHistory))
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Service History ({{ count($serviceHistory) }})</h2></div>
            <div class="card-body">
                @foreach($serviceHistory as $record)
                <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:.88rem">
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-weight:600">{{ $record['type'] ?? 'Service' }}</span>
                        <span style="color:var(--muted);font-size:.78rem">{{ $record['date'] ?? '' }}</span>
                    </div>
                    @if(!empty($record['notes']))
                        <div style="color:var(--muted);margin-top:4px">{{ $record['notes'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
