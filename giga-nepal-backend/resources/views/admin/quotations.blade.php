@extends('admin.layout')
@section('title','RFQ & Quotations')
@section('crumb','B2B request-for-quote pipeline')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">RFQs</div><div class="v tnum">{{ number_format($stats['rfqTotal']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">RFQs open</div><div class="v tnum">{{ number_format($stats['rfqOpen']) }}</div><div class="s">awaiting quote</div></div>
    <div class="kpi"><div class="t">Quotes sent</div><div class="v tnum">{{ number_format($stats['quotesSent']) }}</div><div class="s">pending reply</div></div>
    <div class="kpi"><div class="t">Quotes accepted</div><div class="v tnum">{{ number_format($stats['quotesAccepted']) }}</div><div class="s">won</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>RFQ Requests</h2><span class="sub">Latest 20 · status and quote actions</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>RFQ #</th><th>Company</th><th>Contact</th><th>Requested Items</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($rfqs as $r)
            <tr>
                <td class="mono"><strong>{{ $r->rfq_number }}</strong></td>
                <td>{{ $r->company_name ?? '—' }}<div class="sub">{{ $r->currency ?? 'USD' }}</div></td>
                <td>{{ $r->contact_email ?? $r->contact_name ?? '—' }}<div class="sub">{{ $r->contact_phone ?? '' }}</div></td>
                <td>
                    @forelse (($rfqItems[$r->id] ?? collect())->take(3) as $item)
                        <div>{{ $item->name }} <span class="sub">x {{ number_format((float) $item->quantity, 3) }}</span></div>
                    @empty
                        <span class="sub">No item rows yet</span>
                    @endforelse
                </td>
                <td><span class="badge {{ $r->status === 'open' ? 'b-info' : ($r->status === 'closed' ? 'b-ok' : 'b-muted') }}">{{ $r->status }}</span></td>
                <td style="min-width:420px">
                    <form method="post" action="/admin/rfqs/{{ $r->id }}/status" style="display:grid;grid-template-columns:130px 1fr auto;gap:8px;margin-bottom:8px">@csrf
                        <select class="control" name="status" style="min-height:34px">
                            @foreach (['open','quoted','accepted','closed','cancelled'] as $s)<option value="{{ $s }}" @selected($r->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                        <input class="control" name="notes" maxlength="3000" placeholder="Internal note" style="min-height:34px">
                        <button class="btn" type="submit">Update</button>
                    </form>
                    <form method="post" action="/admin/rfqs/{{ $r->id }}/quotations" class="form-stack" style="gap:8px">@csrf
                        @foreach(range(0,2) as $i)
                            @php $seed = optional(($rfqItems[$r->id] ?? collect())->values()->get($i)); @endphp
                            <div style="display:grid;grid-template-columns:1fr 100px 100px 100px;gap:8px">
                                <input class="control" name="items[{{ $i }}][name]" @if($i===0) required @endif maxlength="255" value="{{ $seed->name }}" placeholder="Line item {{ $i + 1 }}">
                                <input class="control" name="items[{{ $i }}][quantity]" @if($i===0) required @endif type="number" step="0.001" min="0.001" value="{{ $seed->quantity ?: ($i === 0 ? 1 : '') }}" placeholder="Qty">
                                <input class="control" name="items[{{ $i }}][unit_price]" @if($i===0) required @endif type="number" step="0.0001" min="0" placeholder="Unit">
                                <input class="control" name="items[{{ $i }}][tax_amount]" type="number" step="0.01" min="0" placeholder="Tax">
                            </div>
                        @endforeach
                        <div style="display:grid;grid-template-columns:1fr 140px auto;gap:8px">
                            <input class="control" name="notes" maxlength="3000" placeholder="Quote note">
                            <input class="control" name="valid_until" type="date">
                            <button class="btn btn-primary" type="submit">Create Quote</button>
                        </div>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No RFQs yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Quotations</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Quote #</th><th class="num">Total</th><th>Status</th><th>Valid until</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($quotations as $q)
            <tr>
                <td class="mono"><strong>{{ $q->quote_number }}</strong><div class="sub">RFQ #{{ $q->rfq_request_id ?? '—' }}</div><a class="btn btn-ghost" href="/admin/quotations/{{ $q->id }}/preview" target="_blank" style="margin-top:6px">Preview</a></td>
                <td class="num tnum">{{ number_format($q->grand_total, 2) }} {{ $q->currency }}</td>
                <td><span class="badge {{ $q->status === 'accepted' ? 'b-ok' : ($q->status === 'sent' ? 'b-info' : 'b-muted') }}">{{ $q->status }}</span></td>
                <td class="sub">{{ $q->valid_until ?? '—' }}</td>
                <td>
                    <form method="post" action="/admin/quotations/{{ $q->id }}/status" style="display:flex;gap:8px;flex-wrap:wrap">@csrf
                        <select class="control" name="status" style="min-height:34px">
                            @foreach (['draft','sent','accepted','rejected','expired'] as $s)<option value="{{ $s }}" @selected($q->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                        <input class="control" name="notes" maxlength="3000" placeholder="Note" style="min-height:34px;max-width:220px">
                        <button class="btn" type="submit">Save</button>
                    </form>
                    <details style="margin-top:8px"><summary class="btn btn-ghost">Lines</summary>
                        <div style="padding-top:8px">
                            @forelse(($quotationItems[$q->id] ?? collect()) as $item)
                                <div style="display:flex;justify-content:space-between;gap:8px;padding:5px 0;border-bottom:1px solid var(--line)"><span>{{ $item->name }} · {{ number_format((float)$item->quantity, 3) }} x {{ number_format((float)$item->unit_price, 4) }}</span><form method="post" action="/admin/quotations/{{ $q->id }}/items/{{ $item->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
                            @empty <div class="sub">No line items.</div> @endforelse
                            <form method="post" action="/admin/quotations/{{ $q->id }}/items" class="form-grid" style="margin-top:8px">@csrf<input class="control" name="name" required placeholder="Item"><input class="control" name="sku" placeholder="SKU"><input class="control" name="quantity" required type="number" step="0.001" min="0.001" placeholder="Qty"><input class="control" name="unit_price" required type="number" step="0.0001" min="0" placeholder="Unit"><input class="control" name="tax_amount" type="number" step="0.01" min="0" placeholder="Tax"><button class="btn" type="submit">Add line</button></form>
                        </div>
                    </details>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No quotations yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="note" style="margin-top:16px">RFQs arrive from the public sales API. Admin quote creation is safe-mode: it creates draft quotes and marks the RFQ quoted; sending/approval remains a controlled status action.</div>

@endsection
