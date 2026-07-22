@extends('frontend.account.layout')
@section('title', 'Saved Products — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Saved parts</h1><p>Parts and project lists saved from the NeoGiga catalogue.</p></div><a class="account-button" href="/en/products">Browse products</a></header>
@if($saved->isEmpty())<div class="account-panel account-empty">No saved parts yet.</div>@else
<div class="account-table-wrap"><table class="account-table"><thead><tr><th>Part</th><th>MPN / SKU</th><th>List</th><th>Price</th><th>Saved</th></tr></thead><tbody>
@foreach($saved as $item)<tr><td><a href="/en/products/{{ $item->slug }}"><strong>{{ $item->name }}</strong></a></td><td class="mono">{{ $item->mpn ?: $item->sku ?: '—' }}</td><td>{{ $item->list_name }}</td><td>{{ $item->list_price ? number_format($item->list_price,2) : 'RFQ' }}</td><td>{{ \Carbon\Carbon::parse($item->saved_at)->diffForHumans() }}</td></tr>@endforeach
</tbody></table></div>@endif
@endsection
