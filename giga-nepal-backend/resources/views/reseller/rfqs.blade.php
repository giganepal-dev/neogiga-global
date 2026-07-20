@extends('reseller.layout')
@section('title','RFQ Bids')
@section('content')
<div class="page-intro"><h1>RFQ invitations</h1><p>Bid on RFQs assigned by NeoGiga admin. Customer identity is never shown — country only.</p></div>
@forelse($assignments as $assignment)
<div class="card">
    <div class="card-h"><h2>RFQ #{{ $assignment->rfq?->rfq_number ?? $assignment->rfq_id }}</h2><span class="badge b-info">{{ $assignment->status }}</span></div>
    @if($assignment->rfq?->items)
    <form method="post" action="/reseller/rfqs/{{ $assignment->id }}/bid">
        @csrf
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Item</th><th>Qty</th><th>Your unit price</th></tr></thead>
            <tbody>
                @foreach($assignment->rfq->items as $item)
                <tr>
                    <td>{{ $item->name }}<input type="hidden" name="items[{{ $loop->index }}][rfq_item_id]" value="{{ $item->id }}"></td>
                    <td class="tnum">{{ $item->quantity }}<input type="hidden" name="items[{{ $loop->index }}][quantity]" value="{{ $item->quantity }}"></td>
                    <td><input class="control" type="number" step="0.01" min="0" name="items[{{ $loop->index }}][unit_price]" required></td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        <div class="card-body">
            <div class="field"><label>Cover note</label><textarea class="control" name="cover_note" rows="3"></textarea></div>
            <div class="field field--sm"><label>Lead time (days)</label><input class="control" type="number" name="lead_time_days" min="1"></div>
            <button type="submit" class="btn btn-primary">Submit bid</button>
        </div>
    </form>
    @endif
</div>
@empty
<div class="card empty-card"><p>No RFQ invitations yet.</p></div>
@endforelse
{{ $assignments->links() }}
@endsection
