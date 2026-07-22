@extends('frontend.layout')
@section('title','Request a Bulk Quote (RFQ) — NeoGiga')
@section('description','Request wholesale pricing for electronic components. Upload BOM or add parts manually. NeoGiga sales team replies with a formal quotation.')

@section('content')
@if(session('rfq_submitted'))
    <x-confirmation
        title="RFQ Submitted Successfully"
        :reference="session('rfq_submitted')['reference']"
        message="Thank you. Your quotation request has been received by NeoGiga. Our sourcing team will review the requested parts and respond with a formal quotation."
        :summary="[
            'RFQ Reference' => session('rfq_submitted')['reference'],
            'Parts Requested' => session('rfq_submitted')['items_count'].' part(s)',
            'Contact Email' => session('rfq_submitted')['email'] ?? '—',
            'Status' => 'Received',
        ]"
        emailStatus="queued"
        nextStep="We will notify you when the quotation is prepared or when additional information is required."
        :primaryAction="['label' => 'Submit Another RFQ', 'url' => url()->current()]"
        :secondaryActions="[
            ['label' => 'Upload BOM', 'url' => url('/en/bom')],
            ['label' => 'Browse Products', 'url' => url('/en/products')],
        ]"
    />
@else
<style nonce="{{ $csp_nonce ?? '' }}">
    .rfq-wrap{max-width:860px;margin:0 auto;padding:24px 0 64px}
    .rfq-hero{text-align:center;margin-bottom:28px}
    .rfq-hero h1{font-size:1.6rem;margin:0 0 8px}
    .rfq-hero p{color:var(--muted);margin:0;max-width:60ch;margin-inline:auto}
    .rfq-card{background:var(--glass);border:1px solid var(--line);border-radius:14px;padding:24px;margin-bottom:16px}
    .rfq-card h2{font-size:1rem;margin:0 0 16px;display:flex;align-items:center;gap:8px}
    .rfq-card h2 .step{width:28px;height:28px;border-radius:50%;background:var(--cyan);color:#003640;display:grid;place-items:center;font-weight:800;font-size:.8rem;flex:none}
    .rfq-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:640px){.rfq-grid{grid-template-columns:1fr}}
    .rfq-field{display:grid;gap:4px;margin-bottom:10px}
    .rfq-field label{font-weight:600;font-size:.8rem;color:var(--muted)}
    .rfq-field input,.rfq-field textarea,.rfq-field select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line);border-radius:9px;background:var(--s1);color:var(--on);font:inherit}
    .rfq-field textarea{resize:vertical;min-height:80px}
    .rfq-lines{margin:0 -8px}
    .rfq-line{display:grid;grid-template-columns:minmax(0,1fr) minmax(80px,100px) minmax(100px,140px) auto;gap:8px;align-items:end;padding:10px 8px;border-bottom:1px solid var(--line)}
    .rfq-line:last-child{border-bottom:0}
    .rfq-line .rfq-field{margin-bottom:0}
    @media(max-width:768px){.rfq-line{grid-template-columns:1fr 1fr;gap:6px}.rfq-line .btn-remove{grid-column:1/-1;justify-self:end}}
    @media(max-width:430px){.rfq-line{grid-template-columns:1fr;gap:4px}}
    .btn-sm{min-height:36px;padding:0 12px;font-size:.8rem}
    .btn-remove{display:inline-flex;align-items:center;gap:4px;color:#ef4444;background:none;border:1px solid transparent;border-radius:6px;cursor:pointer;font-size:.82rem;padding:6px 10px;min-height:44px}.btn-remove:hover{background:#fef2f2;border-color:#fecaca}
    .rfq-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.9rem}
    .rfq-msg.ok{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.2)}
    .rfq-msg.err{background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2)}
    .rfq-product-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:rgba(40,216,251,.08);border:1px solid rgba(40,216,251,.15);border-radius:8px;font-size:.78rem;margin:4px}
    .rfq-summary{font-size:.85rem}
    .rfq-summary td{padding:6px 12px}
    .rfq-summary td:last-child{text-align:right;font-weight:600}
</style>

<div class="rfq-wrap">
    <div class="rfq-hero">
        <h1>Request a Bulk Quote</h1>
        <p>Add parts manually, paste a list, or upload a BOM. Our sales team provides a formal quotation within 24 hours.</p>
    </div>

    @if(session('status'))<div class="rfq-msg ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rfq-msg err">{{ $errors->first() }}</div>@endif

    <form method="post" action="/rfq" enctype="multipart/form-data">
        @csrf
        @if($product)<input type="hidden" name="product_slug" value="{{ $product->slug }}">@endif

        {{-- STEP 1: Products --}}
        <div class="rfq-card">
            <h2><span class="step">1</span> What do you need?</h2>

            @if($product)
                <div style="margin-bottom:16px">
                    <div class="rfq-product-chip">
                        <strong>{{ $product->name }}</strong>
                        @if($product->mpn) · {{ $product->mpn }}@endif
                        @if($product->brand) · {{ $product->brand->name }}@endif
                    </div>
                </div>
            @endif

            <div id="rfq-lines" class="rfq-lines">
                <div class="rfq-line">
                    <div class="rfq-field"><label>Part name / description *</label><input name="item_name" required maxlength="190" value="{{ old('item_name', $product->name ?? '') }}" placeholder="e.g. STM32F103C8T6 microcontroller"></div>
                    <div class="rfq-field"><label>Qty *</label><input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required></div>
                    <div class="rfq-field"><label>Target price</label><input type="number" name="target_price" min="0" step="0.01" value="{{ old('target_price') }}" placeholder="Opt"></div>
<button type="button" class="btn-remove" title="Remove this part" aria-label="Remove this part" onclick="removeRfqLine(this)"><x-icon name="x-circle" size="18"/> Remove</button>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addRfqLine()">+ Add another part</button>
                <a href="/en/bom" class="btn btn-ghost btn-sm">Upload BOM instead</a>
            </div>
            <input type="hidden" name="mpn" value="{{ old('mpn', $product->mpn ?? '') }}">
        </div>

        {{-- STEP 2: Details --}}
        <div class="rfq-card">
            <h2><span class="step">2</span> Delivery &amp; Requirements</h2>
            <div class="rfq-grid">
                <div class="rfq-field"><label>Country / Region *</label><input name="country" required maxlength="100" value="{{ old('country') }}" placeholder="e.g. Nepal"></div>
                <div class="rfq-field"><label>Required by</label><input type="date" name="required_date" value="{{ old('required_date') }}"></div>
            </div>
            <div class="rfq-field"><label>Message / special instructions</label><textarea name="message" rows="3" maxlength="2000" placeholder="Any specific requirements, delivery preferences, or notes...">{{ old('message') }}</textarea></div>
        </div>

        {{-- STEP 3: Contact --}}
        <div class="rfq-card">
            <h2><span class="step">3</span> Your Contact Information</h2>
            <div class="rfq-grid">
                <div class="rfq-field"><label>Your name *</label><input name="contact_name" required maxlength="190" value="{{ old('contact_name') }}"></div>
                <div class="rfq-field"><label>Email *</label><input type="email" name="contact_email" required maxlength="190" value="{{ old('contact_email') }}"></div>
                <div class="rfq-field"><label>Phone</label><input name="contact_phone" maxlength="40" value="{{ old('contact_phone') }}"></div>
                <div class="rfq-field"><label>Company</label><input name="company_name" maxlength="190" value="{{ old('company_name') }}"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;min-height:52px;font-size:1rem;font-weight:700">
            Submit RFQ
        </button>
        <p style="text-align:center;font-size:.78rem;color:var(--faint);margin-top:8px">Our sales team typically responds within 24 hours with a formal quotation.</p>
    </form>
</div>

<script nonce="{{ $csp_nonce ?? '' }}">
function addRfqLine() {
    var lines = document.getElementById('rfq-lines');
    var first = lines.querySelector('.rfq-line');
    var clone = first.cloneNode(true);
    var inputs = clone.querySelectorAll('input');
    for (var i = 0; i < inputs.length; i++) {
        if (i === 1) inputs[i].value = '1';
        else inputs[i].value = '';
    }
    lines.appendChild(clone);
}
function removeRfqLine(btn) {
    var all = document.querySelectorAll('.rfq-line');
    if (all.length <= 1) return;
    var row = btn.closest('.rfq-line');
    if (row) row.remove();
}
</script>
@endif
@endsection
