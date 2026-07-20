<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Cashier — NeoGiga</title>
    <style>
        :root { --bg:#f5f5f5; --card:#fff; --text:#1a1a1a; --muted:#666; --accent:#2563eb; --danger:#dc2626; --success:#16a34a; --border:#e5e5e5; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; flex-direction: column; }
        .toolbar { display:flex; gap:12px; padding:10px 16px; background:var(--card); border-bottom:1px solid var(--border); flex-wrap:wrap; align-items:center; }
        .toolbar label { font-size:12px; color:var(--muted); display:block; }
        .toolbar select, .toolbar input { padding:6px 8px; border:1px solid var(--border); border-radius:6px; min-width:120px; }
        .body { flex:1; display:flex; min-height:0; }
        .sidebar { width:320px; background:var(--card); border-right:1px solid var(--border); display:flex; flex-direction:column; }
        .main { flex:1; display:flex; flex-direction:column; }
        .search-box { padding:12px; border-bottom:1px solid var(--border); }
        .search-box input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:6px; }
        .product-list { flex:1; overflow-y:auto; padding:8px; }
        .product-item { padding:8px 10px; border-radius:6px; cursor:pointer; display:flex; justify-content:space-between; font-size:13px; }
        .product-item:hover { background:#f0f7ff; }
        .cart-header { padding:16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; }
        .cart-items { flex:1; overflow-y:auto; padding:16px; }
        .cart-item { display:flex; gap:8px; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:14px; }
        .cart-footer { padding:16px; border-top:2px solid var(--text); }
        .cart-total { display:flex; justify-content:space-between; font-size:20px; font-weight:700; margin-bottom:12px; }
        .payment-buttons { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .payment-buttons button { padding:12px; border:none; border-radius:6px; font-weight:600; cursor:pointer; color:#fff; }
        .btn-cash { background:var(--success); } .btn-card { background:var(--accent); } .btn-clear { background:var(--danger); }
        .receipt { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center; z-index:1000; }
        .receipt.active { display:flex; }
        .receipt-content { background:#fff; padding:24px; border-radius:8px; max-width:360px; width:100%; font-size:13px; }
        .empty-state { text-align:center; color:var(--muted); padding:48px 16px; }
        .status { padding:4px 8px; border-radius:4px; font-size:12px; background:#f0fdf4; color:var(--success); }
    </style>
</head>
<body>
<div class="toolbar">
    <div><label>Warehouse ID</label><input type="number" id="warehouseId" value="1" min="1"></div>
    <div><label>Marketplace ID</label><input type="number" id="marketplaceId" placeholder="Optional"></div>
    <div><label>Terminal</label><select id="terminalSelect"><option value="">Auto</option></select></div>
    <div><label>Customer</label><input type="text" id="customerSearch" placeholder="Search account…"></div>
    <button type="button" onclick="openSession()" style="padding:8px 12px;border:none;background:var(--accent);color:#fff;border-radius:6px;cursor:pointer;margin-top:16px">Open session</button>
    <span class="status" id="sessionLabel">No session</span>
</div>
<div class="body">
    <div class="sidebar">
        <div class="search-box"><input type="text" id="search" placeholder="Search SKU or name…" autofocus></div>
        <div class="product-list" id="products"><div class="empty-state">Type to search products</div></div>
    </div>
    <div class="main">
        <div class="cart-header"><h2>Sale #<span id="saleId">New</span></h2><span class="status" id="status">Open</span></div>
        <div class="cart-items" id="cartItems"><div class="empty-state">Scan or search to add items</div></div>
        <div class="cart-footer">
            <div class="cart-total"><span>Total</span><span id="total">$0.00</span></div>
            <div class="payment-buttons">
                <button class="btn-cash" onclick="checkout('cash')">Cash</button>
                <button class="btn-card" onclick="checkout('card')">Card</button>
                <button class="btn-clear" onclick="clearCart()">Clear</button>
            </div>
        </div>
    </div>
</div>
<div class="receipt" id="receipt"><div class="receipt-content" id="receiptContent"></div></div>
<script>
let cart = [], saleId = null, sessionId = null, receiptUrl = null, selectedCustomerId = null;
const base = '/pos/cashier';

async function loadTerminals() {
    const mp = document.getElementById('marketplaceId').value;
    const res = await fetch(base + '/terminals' + (mp ? '?marketplace_id=' + mp : ''), { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    const sel = document.getElementById('terminalSelect');
    sel.innerHTML = '<option value="">Auto</option>' + (data.data || []).map(t => `<option value="${t.id}">${esc(t.terminal_name || t.terminal_code)}</option>`).join('');
}

async function openSession() {
    const body = {
        warehouse_id: parseInt(document.getElementById('warehouseId').value, 10),
        marketplace_id: document.getElementById('marketplaceId').value ? parseInt(document.getElementById('marketplaceId').value, 10) : null,
        pos_terminal_id: document.getElementById('terminalSelect').value ? parseInt(document.getElementById('terminalSelect').value, 10) : null,
    };
    const res = await fetch(base + '/session/open', { method:'POST', headers: jsonHeaders(), body: JSON.stringify(body) });
    const data = await res.json();
    if (!res.ok) return alert(data.message || 'Failed to open session');
    sessionId = data.data?.id;
    document.getElementById('sessionLabel').textContent = 'Session #' + sessionId;
}

document.getElementById('search').addEventListener('input', debounce(async (e) => {
    const q = e.target.value.trim();
    if (q.length < 2) { document.getElementById('products').innerHTML = '<div class="empty-state">Type to search</div>'; return; }
    const res = await fetch('/api/v1/pos/products/search?q=' + encodeURIComponent(q));
    const data = await res.json();
    renderProducts(data.data || []);
}, 300));

document.getElementById('customerSearch').addEventListener('input', debounce(async (e) => {
    const q = e.target.value.trim();
    if (q.length < 2) { selectedCustomerId = null; return; }
    const mp = document.getElementById('marketplaceId').value;
    const res = await fetch(base + '/customers/search?q=' + encodeURIComponent(q) + (mp ? '&marketplace_id=' + mp : ''), { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if ((data.data || []).length === 1) { selectedCustomerId = data.data[0].id; }
}, 400));

function renderProducts(products) {
    const el = document.getElementById('products');
    if (!products.length) { el.innerHTML = '<div class="empty-state">No products found</div>'; return; }
    el.innerHTML = products.map(p => {
        const price = parseFloat(p.sale_price || p.base_price || 0);
        return `<div class="product-item" onclick="addToCart(${p.id}, '${esc(p.name)}', '${esc(p.sku||'')}', ${price})"><div><strong>${esc(p.name)}</strong><br><span style="color:var(--muted);font-size:11px">${esc(p.sku||'')}</span></div><div style="font-weight:600">$${price.toFixed(2)}</div></div>`;
    }).join('');
}

function addToCart(id, name, sku, price) {
    const existing = cart.find(i => i.product_id === id);
    if (existing) { existing.qty++; existing.total = existing.qty * existing.price; }
    else { cart.push({ product_id: id, name, sku, price, qty: 1, total: price }); }
    renderCart();
}

function renderCart() {
    const el = document.getElementById('cartItems');
    if (!cart.length) { el.innerHTML = '<div class="empty-state">Cart empty</div>'; document.getElementById('total').textContent = '$0.00'; return; }
    el.innerHTML = cart.map((item, i) => `<div class="cart-item"><div style="flex:1"><strong>${esc(item.name)}</strong><br><span style="color:var(--muted);font-size:12px">${esc(item.sku)}</span></div><div>${item.qty}</div><div style="width:70px;text-align:right">$${item.total.toFixed(2)}</div><button onclick="removeItem(${i})" style="border:none;background:none;color:var(--danger);cursor:pointer">×</button></div>`).join('');
    document.getElementById('total').textContent = '$' + cart.reduce((s,i)=>s+i.total,0).toFixed(2);
}

function removeItem(i) { cart.splice(i,1); renderCart(); }
function clearCart() { if (confirm('Clear cart?')) { cart = []; renderCart(); } }

async function checkout(method) {
    if (!cart.length) return alert('Cart is empty');
    if (!sessionId) { await openSession(); if (!sessionId) return; }
    const total = cart.reduce((s,i)=>s+i.total,0);
    const res = await fetch(base + '/sales', {
        method:'POST', headers: jsonHeaders(),
        body: JSON.stringify({
            pos_session_id: sessionId,
            pos_customer_account_id: selectedCustomerId,
            payment_method: method,
            items: cart.map(i => ({ product_id: i.product_id, quantity: i.qty, unit_price: i.price }))
        })
    });
    const data = await res.json();
    if (!res.ok) return alert(data.message || 'Checkout failed');
    saleId = data.data?.id;
    receiptUrl = data.data?.receipt_url;
    document.getElementById('saleId').textContent = saleId ? '#'+saleId : 'New';
    document.getElementById('status').textContent = 'Paid';
    showReceipt(method, total, data.data?.receipt_url);
}

function showReceipt(method, total, url) {
    const qr = url ? `<img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(url)}" alt="QR" style="display:block;margin:12px auto">` : '';
    document.getElementById('receiptContent').innerHTML = `<h3 style="text-align:center">NeoGiga POS</h3><p>Sale #${saleId} · ${method.toUpperCase()}</p>${cart.map(i=>`<div style="display:flex;justify-content:space-between"><span>${esc(i.name)} ×${i.qty}</span><span>$${i.total.toFixed(2)}</span></div>`).join('')}<div style="display:flex;justify-content:space-between;font-weight:700;margin-top:8px;border-top:1px dashed #ccc;padding-top:8px"><span>Total</span><span>$${total.toFixed(2)}</span></div>${qr}<button onclick="newSale()" style="width:100%;margin-top:12px;padding:10px;border:none;border-radius:6px;background:var(--accent);color:#fff;cursor:pointer">New sale</button>`;
    document.getElementById('receipt').classList.add('active');
}

function newSale() { cart=[]; saleId=null; receiptUrl=null; document.getElementById('saleId').textContent='New'; document.getElementById('status').textContent='Open'; document.getElementById('receipt').classList.remove('active'); renderCart(); }
function jsonHeaders() { return { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': getCsrf() }; }
function getCsrf() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; }
function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }
loadTerminals();
</script>
</body>
</html>
