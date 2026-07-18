{{-- Chat with Seller Modal --}}
@props(['product' => null, 'order' => null])
@php $targetId = $product->id ?? $order->id ?? 0; $targetType = $product ? 'product' : 'order'; @endphp
<div id="chat-modal-{{ $targetId }}" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
<div class="modal-card" style="background:var(--s1);border:1px solid var(--line);border-radius:14px;max-width:480px;width:calc(100% - 32px);max-height:80vh;overflow-y:auto;padding:24px;color:var(--on)" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h2 style="margin:0;font-size:1.2rem">Chat with Seller</h2>
        <button onclick="document.getElementById('chat-modal-{{ $targetId }}').style.display='none'" style="background:none;border:none;color:var(--muted);font-size:1.5rem;cursor:pointer">&times;</button>
    </div>

    @if($product)
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;padding:10px;background:rgba(40,216,251,.04);border-radius:10px">
            @php($img = $product->images->first())
            <img src="{{ $img?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" style="width:48px;height:36px;object-fit:contain;background:#081527;border-radius:6px" alt="">
            <div><strong style="font-size:.85rem">{{ $product->name }}</strong>
            <div style="font-size:.72rem;color:var(--faint)">SKU: {{ $product->sku }} · MPN: {{ $product->mpn }}</div></div>
        </div>
    @endif

    <form method="post" action="/api/v1/support/inquire" onsubmit="return handleChatSubmit(this, {{ $targetId }})" class="chat-form">
        @csrf
        <input type="hidden" name="about_type" value="{{ $targetType }}">
        <input type="hidden" name="about_id" value="{{ $targetId }}">
        @if($product)
            <input type="hidden" name="product_name" value="{{ $product->name }}">
            <input type="hidden" name="product_sku" value="{{ $product->sku }}">
            <input type="hidden" name="product_mpn" value="{{ $product->mpn }}">
        @endif

        <div class="field" style="margin-bottom:12px">
            <label style="font-weight:600;font-size:.8rem;color:var(--muted)">Subject</label>
            <select name="subject" class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" required>
                <option value="">Select topic...</option>
                <option value="stock">Stock Availability</option>
                <option value="pricing">Bulk / Tier Pricing</option>
                <option value="shipping">Shipping & Delivery</option>
                <option value="technical">Technical Specification</option>
                <option value="warranty">Warranty & Returns</option>
                <option value="other">Other Inquiry</option>
            </select>
        </div>

        <div class="field" style="margin-bottom:12px">
            <label style="font-weight:600;font-size:.8rem;color:var(--muted)">Quantity (optional)</label>
            <input type="number" name="quantity" min="1" max="1000000" class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" placeholder="How many units?">
        </div>

        <div class="field" style="margin-bottom:16px">
            <label style="font-weight:600;font-size:.8rem;color:var(--muted)">Your Message</label>
            <textarea name="message" class="control" rows="4" required style="width:100%;border:1px solid var(--line);border-radius:8px;padding:12px;background:var(--bg);color:var(--on);resize:vertical" placeholder="Describe your inquiry..."></textarea>
        </div>

        <button type="submit" class="btn" style="width:100%;background:var(--cyan);color:#003640;border:none;font-weight:700">Send Inquiry</button>
        <div id="chat-status-{{ $targetId }}" style="text-align:center;margin-top:8px;font-size:.82rem;display:none"></div>
    </form>
</div>
</div>

<script>
function handleChatSubmit(form, targetId) {
    event.preventDefault();
    var status = document.getElementById('chat-status-' + targetId);
    status.style.display = 'block';
    status.style.color = 'var(--cyan)';
    status.textContent = 'Sending...';

    fetch(form.action, {method:'POST',body:new FormData(form)})
        .then(r => r.json())
        .then(data => {
            status.style.color = '#34d399';
            status.textContent = '✓ Inquiry sent! Seller will respond within 24 hours.';
            form.reset();
            setTimeout(function(){ document.getElementById('chat-modal-'+targetId).style.display='none'; }, 2000);
        })
        .catch(err => {
            status.style.color = '#ef4444';
            status.textContent = 'Failed to send. Please try again.';
        });
    return false;
}
</script>
