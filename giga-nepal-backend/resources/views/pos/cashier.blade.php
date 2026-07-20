<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cashier — NeoGiga</title>
    <style>
        :root {
            --bg: #f5f5f5;
            --card: #fff;
            --text: #1a1a1a;
            --muted: #666;
            --accent: #2563eb;
            --danger: #dc2626;
            --success: #16a34a;
            --border: #e5e5e5;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; }
        .sidebar { width: 320px; background: var(--card); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .main { flex: 1; display: flex; flex-direction: column; }
        .search-box { padding: 12px; border-bottom: 1px solid var(--border); }
        .search-box input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .search-box input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .product-list { flex: 1; overflow-y: auto; padding: 8px; }
        .product-item { padding: 8px 10px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .product-item:hover { background: #f0f7ff; }
        .product-item .sku { color: var(--muted); font-size: 11px; }
        .product-item .price { font-weight: 600; font-size: 14px; }
        .cart-area { flex: 1; display: flex; flex-direction: column; }
        .cart-header { padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .cart-header h2 { font-size: 18px; }
        .cart-items { flex: 1; overflow-y: auto; padding: 16px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
        .cart-item .qty { display: flex; align-items: center; gap: 6px; }
        .cart-item .qty button { width: 24px; height: 24px; border: 1px solid var(--border); border-radius: 4px; background: var(--card); cursor: pointer; font-size: 14px; }
        .cart-item .qty span { min-width: 24px; text-align: center; font-weight: 600; }
        .cart-item .remove { color: var(--danger); cursor: pointer; background: none; border: none; font-size: 16px; }
        .cart-footer { padding: 16px; border-top: 2px solid var(--text); }
        .cart-total { display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .payment-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .payment-buttons button { padding: 12px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; color: #fff; }
        .btn-cash { background: var(--success); }
        .btn-card { background: var(--accent); }
        .btn-hold { background: #9ca3af; }
        .btn-clear { background: var(--danger); }
        .btn-print { background: #7c3aed; grid-column: span 2; }
        .receipt { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.5); justify-content: center; align-items: center; z-index: 1000; }
        .receipt.active { display: flex; }
        .receipt-content { background: #fff; padding: 24px; border-radius: 8px; max-width: 320px; width: 100%; font-family: monospace; font-size: 12px; }
        .receipt-content h3 { text-align: center; margin-bottom: 12px; font-size: 16px; }
        .receipt-content .line { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .receipt-content .total { border-top: 1px dashed #000; margin-top: 8px; padding-top: 8px; font-weight: 700; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; background: #f0fdf4; color: var(--success); }
        .empty-state { text-align: center; color: var(--muted); padding: 48px 16px; }
    </style>
</head>
<body>
    <!-- Product Search Sidebar -->
    <div class="sidebar">
        <div class="search-box">
            <input type="text" id="search" placeholder="Search by SKU, MPN, or name..." autofocus>
        </div>
        <div class="product-list" id="products">
            <div class="empty-state">Type to search products</div>
        </div>
    </div>

    <!-- Cart Area -->
    <div class="main">
        <div class="cart-header">
            <h2>Sale #<span id="saleId">New</span></h2>
            <span class="status" id="status">Open</span>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="empty-state">Scan or search to add items</div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span id="total">$0.00</span>
            </div>
            <div class="payment-buttons">
                <button class="btn-cash" onclick="checkout('cash')">Cash</button>
                <button class="btn-card" onclick="checkout('card')">Card</button>
                <button class="btn-hold" onclick="holdSale()">Hold</button>
                <button class="btn-clear" onclick="clearCart()">Clear</button>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="receipt" id="receipt">
        <div class="receipt-content" id="receiptContent"></div>
    </div>

    <script>
        let cart = [];
        let saleId = null;

        // Product search
        document.getElementById('search').addEventListener('input', debounce(async (e) => {
            const q = e.target.value.trim();
            if (q.length < 2) { document.getElementById('products').innerHTML = '<div class="empty-state">Type to search products</div>'; return; }
            try {
                const res = await fetch(`/api/v1/products?q=${encodeURIComponent(q)}&per_page=12`);
                const data = await res.json();
                renderProducts(data.data || []);
            } catch { document.getElementById('products').innerHTML = '<div class="empty-state">Search unavailable</div>'; }
        }, 300));

        function renderProducts(products) {
            const el = document.getElementById('products');
            if (!products.length) { el.innerHTML = '<div class="empty-state">No products found</div>'; return; }
            el.innerHTML = products.map(p => `
                <div class="product-item" onclick="addToCart(${p.id}, '${esc(p.name)}', '${esc(p.sku||p.mpn||'')}', ${p.price||0})">
                    <div><strong>${esc(p.name)}</strong><br><span class="sku">${esc(p.sku||p.mpn||'')}</span></div>
                    <div class="price">$${(p.price||0).toFixed(2)}</div>
                </div>
            `).join('');
        }

        function addToCart(id, name, sku, price) {
            const existing = cart.find(i => i.product_id === id);
            if (existing) { existing.qty++; existing.total = existing.qty * existing.price; }
            else { cart.push({ product_id: id, name, sku, price, qty: 1, total: price }); }
            renderCart();
        }

        function updateQty(index, delta) {
            cart[index].qty = Math.max(1, cart[index].qty + delta);
            cart[index].total = cart[index].qty * cart[index].price;
            renderCart();
        }

        function removeItem(index) { cart.splice(index, 1); renderCart(); }

        function renderCart() {
            const el = document.getElementById('cartItems');
            if (!cart.length) { el.innerHTML = '<div class="empty-state">Scan or search to add items</div>'; document.getElementById('total').textContent = '$0.00'; return; }
            el.innerHTML = cart.map((item, i) => `
                <div class="cart-item">
                    <div style="flex:1"><strong>${esc(item.name)}</strong><br><span style="color:var(--muted);font-size:12px;">${esc(item.sku)}</span></div>
                    <div class="qty">
                        <button onclick="updateQty(${i},-1)">−</button>
                        <span>${item.qty}</span>
                        <button onclick="updateQty(${i},1)">+</button>
                    </div>
                    <div style="width:80px;text-align:right;font-weight:600;">$${item.total.toFixed(2)}</div>
                    <button class="remove" onclick="removeItem(${i})">×</button>
                </div>
            `).join('');
            const total = cart.reduce((s, i) => s + i.total, 0);
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
        }

        async function checkout(method) {
            if (!cart.length) return alert('Cart is empty');
            const total = cart.reduce((s, i) => s + i.total, 0);
            try {
                const res = await fetch('/api/v1/pos/sales', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                    body: JSON.stringify({
                        items: cart.map(i => ({ product_id: i.product_id, quantity: i.qty, unit_price: i.price })),
                        payments: [{ method, amount: total }]
                    })
                });
                if (!res.ok) throw new Error('Checkout failed');
                const data = await res.json();
                saleId = data.data?.id;
                document.getElementById('saleId').textContent = saleId ? '#'+saleId : 'New';
                document.getElementById('status').textContent = 'Completed';
                showReceipt(method, total);
            } catch(e) { alert('Checkout failed. Try again.'); }
        }

        function showReceipt(method, total) {
            const now = new Date().toLocaleString();
            document.getElementById('receiptContent').innerHTML = `
                <h3>NeoGiga POS</h3>
                <div class="line"><span>Date</span><span>${now}</span></div>
                <div class="line"><span>Sale</span><span>#${saleId||'N/A'}</span></div>
                <div class="line"><span>Payment</span><span>${method.toUpperCase()}</span></div>
                <hr>
                ${cart.map(i => `<div class="line"><span>${esc(i.name)} x${i.qty}</span><span>$${i.total.toFixed(2)}</span></div>`).join('')}
                <div class="line total"><span>TOTAL</span><span>$${total.toFixed(2)}</span></div>
                <p style="text-align:center;margin-top:12px;">Thank you!</p>
                <button onclick="newSale()" style="width:100%;padding:10px;margin-top:12px;border:none;border-radius:6px;background:var(--accent);color:#fff;font-weight:600;cursor:pointer;">New Sale</button>
                <button onclick="printReceipt()" style="width:100%;padding:10px;margin-top:6px;border:none;border-radius:6px;background:#7c3aed;color:#fff;font-weight:600;cursor:pointer;">Print</button>
            `;
            document.getElementById('receipt').classList.add('active');
        }

        function newSale() {
            cart = []; saleId = null;
            document.getElementById('saleId').textContent = 'New';
            document.getElementById('status').textContent = 'Open';
            document.getElementById('receipt').classList.remove('active');
            renderCart();
        }

        function holdSale() { alert('Sale held. Retrieve from active holds.'); }
        function clearCart() { if (confirm('Clear current cart?')) { cart = []; renderCart(); } }
        function printReceipt() { window.print(); }
        function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
        function getCsrf() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; }
        function debounce(fn, ms) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); }; }
    </script>
</body>
</html>
