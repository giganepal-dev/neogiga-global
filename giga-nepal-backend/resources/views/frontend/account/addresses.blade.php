@extends('frontend.account.layout')
@section('title','Addresses — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Addresses</h1><p>Billing, delivery, office and warehouse locations.</p></div></header>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Saved addresses</h2><p>Only addresses owned by this customer profile are shown.</p></div></div>
    @if($addresses->isEmpty())<div class="account-empty">No addresses saved yet.</div>@else<div class="account-addresses">
        @foreach($addresses as $address)<article class="account-address"><strong>{{ $address->name ?: ucfirst($address->type) }}</strong> @if($address->is_default)<span class="account-badge approved">Default</span>@endif<p>{{ $address->address_line1 }}@if($address->address_line2)\n{{ $address->address_line2 }}@endif\n{{ collect([$address->original_city ?? null,$address->original_region ?? null,$address->postal_code ?? null])->filter()->join(', ') }}\n{{ $address->original_country ?? '' }}</p><footer><small>{{ ucfirst($address->type) }}</small><form method="post" action="/account/addresses/{{ $address->id }}" onsubmit="return confirm('Remove this address?')">@csrf @method('delete')<button class="account-button danger" type="submit">Remove</button></form></footer></article>@endforeach
    </div>@endif
</section>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Add address</h2><p>Complete your profile first if this is a new account.</p></div></div>
    <form class="account-form" method="post" action="/account/addresses">@csrf<div class="account-form-grid">
        <div class="account-field"><label for="type">Address type</label><select id="type" name="type"><option>shipping</option><option>billing</option><option>office</option><option>warehouse</option></select></div>
        <div class="account-field"><label for="address_name">Recipient / location name</label><input id="address_name" name="name" required></div>
        <div class="account-field"><label for="address_phone">Phone</label><input id="address_phone" name="phone"></div>
        <div class="account-field"><label for="postal_code">Postal code</label><input id="postal_code" name="postal_code"></div>
        <div class="account-field full"><label for="address_line1">Address line 1</label><input id="address_line1" name="address_line1" required></div>
        <div class="account-field full"><label for="address_line2">Address line 2</label><input id="address_line2" name="address_line2"></div>
        <div class="account-field"><label for="original_city">City</label><input id="original_city" name="original_city"></div>
        <div class="account-field"><label for="original_region">State / region</label><input id="original_region" name="original_region"></div>
        <div class="account-field"><label for="original_country">Country</label><input id="original_country" name="original_country"></div>
        <div class="account-field"><label><input type="checkbox" name="is_default" value="1"> Make default for this type</label></div>
    </div><div><button class="account-button" type="submit">Save address</button></div></form>
</section>
@endsection
