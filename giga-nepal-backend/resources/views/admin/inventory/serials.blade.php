@extends('admin.layout')
@section('title', 'Serial Numbers')
@section('crumb', 'Inventory / Serial Numbers')

@section('content')
<div class="page-head">
    <div>
        <h2>Serial Numbers</h2>
        <p>Track individual serialized items for warranty and service history.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/inventory/batches" class="btn btn-ghost">Batches</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div class="kpis">
    <div class="kpi">
        <div class="t">Total Serials</div>
        <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Available</div>
        <div class="v" style="color:var(--ok)">{{ number_format($stats['available']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Sold</div>
        <div class="v">{{ number_format($stats['sold']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Under Warranty</div>
        <div class="v" style="color:var(--info)">{{ number_format($stats['under_warranty']) }}</div>
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="card-h">
        <h2>Serial Numbers ({{ $serials->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search serials..." style="width:200px">
            <select class="control" name="status" style="width:120px">
                <option value="">All Status</option>
                @foreach(['available','reserved','sold','returned','damaged','lost','in_repair'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select class="control" name="warranty" style="width:120px">
                <option value="">All Warranty</option>
                @foreach(['active','expired','void','claimed'] as $w)
                    <option value="{{ $w }}" {{ request('warranty') === $w ? 'selected' : '' }}>{{ ucfirst($w) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Warehouse</th>
                    <th>Status</th>
                    <th>Warranty</th>
                    <th>Sale Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($serials as $s)
                <tr>
                    <td class="mono" style="font-size:.82rem">{{ $s->serial_number }}</td>
                    <td>
                        <span style="font-weight:600">{{ $s->product_name ?? '—' }}</span>
                        @if($s->mpn)
                            <br><span style="color:var(--muted);font-size:.78rem">{{ $s->mpn }}</span>
                        @endif
                    </td>
                    <td class="mono" style="font-size:.82rem">{{ $s->batch_number ?? '—' }}</td>
                    <td>{{ $s->warehouse_name ?? '—' }}</td>
                    <td>
                        @php
                            $badge = match($s->status) {
                                'available' => 'b-ok',
                                'sold' => 'b-muted',
                                'returned' => 'b-info',
                                'damaged' => 'b-danger',
                                'lost' => 'b-danger',
                                'in_repair' => 'b-warn',
                                default => 'b-muted',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_',' ',$s->status)) }}</span>
                    </td>
                    <td>
                        @php
                            $wBadge = match($s->warranty_status) {
                                'active' => 'b-ok',
                                'expired' => 'b-muted',
                                'void' => 'b-danger',
                                'claimed' => 'b-warn',
                                default => 'b-muted',
                            };
                        @endphp
                        <span class="badge {{ $wBadge }}">{{ ucfirst($s->warranty_status ?? '—') }}</span>
                    </td>
                    <td style="font-size:.82rem">{{ $s->sale_date ? \Carbon\Carbon::parse($s->sale_date)->format('M d, Y') : '—' }}</td>
                    <td>
                        <a href="/admin/inventory/serials/{{ $s->id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty">
                        <p>No serial numbers found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $serials->withQueryString()->links() }}
</div>
@endsection
