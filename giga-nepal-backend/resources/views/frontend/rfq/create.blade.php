@extends('frontend.layout')
@section('title','Request a Bulk Quote (RFQ) — NeoGiga')
@section('description','Request wholesale / bulk pricing for engineering components. Our sales team answers every RFQ with a formal quotation.')

@section('content')
<style>
    .rfq-card{max-width:640px;margin:32px auto;padding:32px;border:1px solid rgba(15,23,42,.12);border-radius:14px;background:#fff}
    .rfq-card h1{font-size:1.4rem;margin:0 0 6px}
    .rfq-card .lead-sub{color:#475569;margin:0 0 20px}
    .rfq-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:640px){.rfq-grid{grid-template-columns:1fr}}
    .rfq-card label{display:block;font-weight:600;font-size:.88rem;margin:0 0 4px}
    .rfq-card input,.rfq-card textarea{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit;margin-bottom:12px}
    .rfq-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.92rem}
    .rfq-msg.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .rfq-msg.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .rfq-prod{background:#F0FDFF;border:1px solid #A5F3FC;border-radius:9px;padding:10px 14px;margin-bottom:16px;font-size:.9rem}
</style>
<div class="wrap">
    <div class="rfq-card">
        <h1><x-icon name="rfq" size="22"/> Request a bulk quote</h1>
        <p class="lead-sub">Tell us what you need — our sales team replies with a formal quotation (RFQ → QUO workflow).</p>

        @if (session('status'))<div class="rfq-msg ok">{{ session('status') }}</div>@endif
        @if (isset($errors) && $errors->any())<div class="rfq-msg err">{{ $errors->first() }}</div>@endif

        @if ($product)
            <div class="rfq-prod">Requesting: <strong>{{ $product->name }}</strong>
                @if($product->mpn) · MPN {{ $product->mpn }}@endif
                @if($product->brand) · {{ $product->brand->name }}@endif
            </div>
        @endif

        <form method="post" action="/rfq">
            @csrf
            <input type="hidden" name="product_slug" value="{{ $product->slug ?? '' }}">

            <div class="rfq-grid">
                <div><label for="contact_name">Your name *</label><input id="contact_name" name="contact_name" required maxlength="190" value="{{ old('contact_name') }}"></div>
                <div><label for="contact_email">Email *</label><input id="contact_email" type="email" name="contact_email" required maxlength="190" value="{{ old('contact_email') }}"></div>
                <div><label for="contact_phone">Phone</label><input id="contact_phone" name="contact_phone" maxlength="40" value="{{ old('contact_phone') }}"></div>
                <div><label for="company_name">Company</label><input id="company_name" name="company_name" maxlength="190" value="{{ old('company_name') }}"></div>
                <div><label for="country">Country</label><input id="country" name="country" maxlength="100" value="{{ old('country') }}" placeholder="e.g. Nepal, India"></div>
                <div><label for="required_date">Required by</label><input id="required_date" type="date" name="required_date" value="{{ old('required_date') }}"></div>
            </div>

            <label for="item_name">Part / item *</label>
            <input id="item_name" name="item_name" required maxlength="190" value="{{ old('item_name', $product->name ?? '') }}" placeholder="Part name or description">

            <div class="rfq-grid">
                <div><label for="mpn">Manufacturer Part Number</label><input id="mpn" name="mpn" maxlength="120" value="{{ old('mpn', $product->mpn ?? '') }}"></div>
                <div><label for="quantity">Quantity *</label><input id="quantity" type="number" name="quantity" min="1" step="1" required value="{{ old('quantity') }}"></div>
            </div>

            <label for="target_price">Target unit price (optional)</label>
            <input id="target_price" type="number" name="target_price" min="0" step="0.01" value="{{ old('target_price') }}" placeholder="Your budget per unit">

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4" maxlength="2000" placeholder="Specs, alternatives accepted, delivery location…">{{ old('message') }}</textarea>

            <button class="btn btn-primary" type="submit" style="width:100%"><x-icon name="send" size="16"/> Submit RFQ</button>
        </form>
    </div>
</div>
@endsection
