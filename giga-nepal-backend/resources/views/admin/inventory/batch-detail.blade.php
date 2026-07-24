@extends('admin.layout')
@section('title', 'Batch Details')
@section('crumb', 'Inventory / Batches / ' . ($record->batch_number ?? $record->id))

@section('content')
<div class="page-head">
    <div>
        <h2>Batch {{ $record->batch_number ?? '#' . $record->id }}</h2>
        <p style="color:var(--muted)">{{ $record->product_name ?? '—' }} @if($record->mpn) &middot; {{ $record->mpn }} @endif</p>
    </div>
    <div class="page-actions">
        <a href="/admin/inventory/batches" class="btn btn-ghost">Back to Batches</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
    <div>
        <div class="card">
            <div class="card-h"><h2>Batch Information</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <div class="sub">Batch Number</div>
                        <div style="font-weight:600">{{ $record->batch_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Lot Number</div>
                        <div>{{ $record->lot_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Status</div>
                        <div>
                            @php
                                $badge = match($record->status) {
                                    'active' => 'b-ok',
                                    'quarantined' => 'b-warn',
                                    'expired' => 'b-danger',
                                    'recalled' => 'b-danger',
                                    default => 'b-muted',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($record->status ?? '—') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="sub">Quality Status</div>
                        <div>{{ ucfirst(str_replace('_', ' ', $record->quality_status ?? '—')) }}</div>
                    </div>
                    <div>
                        <div class="sub">Manufacturer Batch</div>
                        <div>{{ $record->manufacturer_batch ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Date Code</div>
                        <div>{{ $record->date_code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Country of Origin</div>
                        <div>{{ $record->country_of_origin ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Warranty (months)</div>
                        <div>{{ $record->warranty_months ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Quantities</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px">
                    <div style="text-align:center">
                        <div style="font-size:24px;font-weight:700">{{ $record->initial_quantity ?? 0 }}</div>
                        <div class="sub">Initial</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:24px;font-weight:700;color:var(--ok)">{{ $record->current_quantity ?? 0 }}</div>
                        <div class="sub">Current</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:24px;font-weight:700;color:var(--warn)">{{ $record->reserved_quantity ?? 0 }}</div>
                        <div class="sub">Reserved</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:24px;font-weight:700;color:var(--danger)">{{ $record->damaged_quantity ?? 0 }}</div>
                        <div class="sub">Damaged</div>
                    </div>
                </div>
                @php
                    $available = ($record->current_quantity ?? 0) - ($record->reserved_quantity ?? 0) - ($record->damaged_quantity ?? 0);
                @endphp
                <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:8px;text-align:center">
                    <div style="font-size:14px;color:var(--muted)">Available Quantity</div>
                    <div style="font-size:28px;font-weight:700;color:{{ $available > 0 ? 'var(--ok)' : 'var(--danger)' }}">{{ $available }}</div>
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
                        <div class="sub">Expiry Date</div>
                        @php $expired = $record->expiry_date && \Carbon\Carbon::parse($record->expiry_date)->isPast(); @endphp
                        <div style="color:{{ $expired ? 'var(--danger)' : 'var(--fg)' }}">
                            {{ $record->expiry_date ? \Carbon\Carbon::parse($record->expiry_date)->format('M d, Y') : '—' }}
                            @if($expired) <span class="badge b-danger">EXPIRED</span> @endif
                        </div>
                    </div>
                    <div>
                        <div class="sub">Best Before</div>
                        <div>{{ $record->best_before_date ? \Carbon\Carbon::parse($record->best_before_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Received At</div>
                        <div>{{ $record->received_at ? \Carbon\Carbon::parse($record->received_at)->format('M d, Y H:i') : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Received By</div>
                        <div>{{ $record->received_by_name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Source</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div>
                        <div class="sub">Supplier</div>
                        <div>{{ $record->supplier_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Warehouse</div>
                        <div>{{ $record->warehouse_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Unit Cost</div>
                        <div style="font-weight:600">{{ $record->unit_cost ? number_format($record->unit_cost, 4) : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($record->quality_notes)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Quality Notes</h2></div>
            <div class="card-body">
                <p style="color:var(--muted)">{{ $record->quality_notes }}</p>
            </div>
        </div>
        @endif

        @if(count($serials) > 0)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Serial Numbers ({{ count($serials) }})</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Serial #</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serials as $s)
                        <tr>
                            <td class="mono" style="font-size:.82rem">{{ $s->serial_number }}</td>
                            <td><span class="badge b-muted">{{ ucfirst($s->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
