@extends('admin.layout')
@section('title','RFQ '.$rfq->rfq_number)
@section('crumb','RFQ detail')
@section('content')

@php
    $badge = fn($s) => match($s) { 'accepted' => 'b-ok', 'open', 'quoted' => 'b-info', default => 'b-muted' };
    $statuses = ['open','quoted','accepted','closed','cancelled'];
    $meta = $rfq->meta ?? [];
@endphp

<div class="card" style="margin-bottom:16px">
    <div class="card-h">
        <div>
            <h2 class="mono">{{ $rfq->rfq_number }}</h2>
            <div class="sub">Received {{ $rfq->created_at?->format('Y-m-d H:i') }}@if(($meta['channel'] ?? '') === 'web_product_page') · via product page RFQ form @endif</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge {{ $badge($rfq->status) }}">{{ $rfq->status }}</span>
            <a class="btn btn-ghost" href="/admin/quotations">Quotations</a>
            <a class="btn btn-ghost" href="/admin/rfqs">Back</a>
        </div>
    </div>
    <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        <div><div class="sub">Contact</div><strong>{{ $rfq->contact_name ?? '—' }}</strong><div class="sub">{{ $rfq->contact_email ?? '' }} {{ $rfq->contact_phone ? '· '.$rfq->contact_phone : '' }}</div></div>
        <div><div class="sub">Company</div>{{ $rfq->company_name ?? '—' }}</div>
        <div><div class="sub">Country</div>{{ $meta['country'] ?? '—' }}</div>
        <div><div class="sub">Required by</div>{{ $meta['required_date'] ?? '—' }}</div>
        <div><div class="sub">Source</div>@if(!empty($meta['source_product_page']))<a href="{{ $meta['source_product_page'] }}" target="_blank">{{ $meta['source_product_page'] }}</a>@else — @endif</div>
    </div>
    @if($rfq->notes)<div style="padding:0 16px 16px"><div class="sub">Message</div>{{ $rfq->notes }}</div>@endif
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Requested items</h2></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Item</th><th>SKU / MPN</th><th class="num">Qty</th><th class="num">Target price</th></tr></thead>
            <tbody>
            @forelse ($rfq->items as $it)
                <tr>
                    <td><strong>{{ $it->name }}</strong></td>
                    <td class="mono">{{ $it->sku ?? '—' }}{{ $it->notes ? ' · '.$it->notes : '' }}</td>
                    <td class="num tnum">{{ number_format($it->quantity) }}</td>
                    <td class="num tnum">{{ $it->target_price !== null ? number_format($it->target_price, 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No items</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Update status</h2></div>
            <form method="post" action="/admin/rfqs/{{ $rfq->id }}/status" class="form-stack" style="padding:16px">@csrf
                <select class="control" name="status" required>
                    @foreach ($statuses as $s)<option value="{{ $s }}" @selected($rfq->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <input class="control" name="notes" maxlength="1000" placeholder="Note (kept in audit trail)">
                <button class="btn btn-primary" type="submit">Save status</button>
            </form>
        </div>

        <div class="card">
            <div class="card-h"><h2>Timeline</h2><span class="sub">Status audit trail</span></div>
            <div style="padding:12px 16px">
            @forelse ($history as $h)
                <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                    <span class="badge {{ $badge($h->status) }}">{{ $h->status }}</span>
                    <span class="sub">from {{ $h->previous_status ?? '—' }} · {{ $h->created_at }}</span>
                    @if($h->notes)<div class="sub">{{ $h->notes }}</div>@endif
                </div>
            @empty
                <div class="empty"><h3>No status changes recorded</h3></div>
            @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
