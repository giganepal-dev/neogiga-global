@php $r = request()->path(); @endphp
<nav class="nav" aria-label="Primary">

{{-- ===== DASHBOARD ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Dashboard</div>
    <div class="nav-section-body">
        <a href="/admin" class="{{ $r==='admin' ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
            Dashboard
        </a>
        <a href="/admin/system-health" class="{{ str_starts_with($r,'admin/system-health') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12h4l2-6 4 12 2-6h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            System Health
        </a>
    </div>
</div>

{{-- ===== CATALOG ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Catalog</div>
    <div class="nav-section-body">
        <a href="/admin/categories" class="{{ str_starts_with($r,'admin/categories') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h18M3 18h12" stroke-linecap="round"/></svg>
            Categories
        </a>
        <a href="/admin/products" class="{{ str_starts_with($r,'admin/products') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8" stroke-linejoin="round"/></svg>
            Products
        </a>
        <a href="/admin/brands" class="{{ str_starts_with($r,'admin/brands') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M21 15l-5-5L8.5 14l-2-2-3.5 4" stroke-linejoin="round"/></svg>
            Brands
        </a>
        <a href="/admin/brand-logos" class="{{ str_starts_with($r,'admin/brand-logos') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="2"/></svg>
            Brand Logos
        </a>
        <a href="/admin/imports/jlcpcb" class="{{ str_starts_with($r,'admin/imports/jlcpcb') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M8 11l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Import Review
        </a>
        <a href="/admin/imports/elecforest" class="{{ str_starts_with($r,'admin/imports/elecforest') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M8 11l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            ElecForest Imports
        </a>
        <a href="/admin/imports/suppliers" class="{{ str_starts_with($r,'admin/imports/suppliers') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Supplier Imports
        </a>
        <a href="/admin/seo" class="{{ str_starts_with($r,'admin/seo') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4" stroke-linecap="round"/></svg>
            SEO
        </a>
        <a href="/admin/media" class="{{ str_starts_with($r,'admin/media') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="2"/></svg>
            Media
        </a>
        <a href="/admin/marketplaces" class="{{ str_starts_with($r,'admin/marketplaces') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18"/></svg>
            Marketplaces
        </a>
    </div>
</div>

{{-- ===== PRICING & TAX ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Pricing &amp; Tax</div>
    <div class="nav-section-body">
        <a href="/admin/pricing" class="{{ str_starts_with($r,'admin/pricing') ? 'active':'' }}">Pricing Engine</a>
        <a href="/admin/tax" class="{{ str_starts_with($r,'admin/tax') ? 'active':'' }}">Tax &amp; Tariff</a>
    </div>
</div>

{{-- ===== COMMERCE ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Commerce</div>
    <div class="nav-section-body">
        <a href="/admin/orders" class="{{ str_starts_with($r,'admin/orders') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2l1.5 3M18 2l-1.5 3M3 6h18l-2 12H5L3 6z" stroke-linejoin="round"/></svg>
            Orders
        </a>
        <a href="/admin/payments" class="{{ str_starts_with($r,'admin/payments') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18" stroke-linecap="round"/></svg>
            Payments &amp; Wallet
        </a>
        <a href="/admin/promotions" class="{{ str_starts_with($r,'admin/promotions') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2 7h7l-5.5 4 2 7L12 16l-5.5 4 2-7L3 9h7l2-7z"/></svg>
            Coupons &amp; Gift Cards
        </a>
        <a href="/admin/pos" class="{{ $r==='admin/pos' ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8" stroke-linecap="round"/></svg>
            POS Dashboard
        </a>
        <a href="/admin/pos/manage" class="{{ str_starts_with($r,'admin/pos/manage') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/></svg>
            Registers
        </a>
        <a href="/admin/pos/history" class="{{ str_starts_with($r,'admin/pos/history') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Register History
        </a>
        <a href="/admin/pos/z-reports" class="{{ str_starts_with($r,'admin/pos/z-reports') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 7h18M8 7v10M16 7v10" stroke-linecap="round"/></svg>
            Z-Reports
        </a>
        <a href="/admin/pos/rewards" class="{{ str_starts_with($r,'admin/pos/rewards') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2 7h7l-5.5 4 2 7L12 16l-5.5 4 2-7L3 9h7l2-7z"/></svg>
            Rewards
        </a>
        <a href="/admin/pos/instalments" class="{{ str_starts_with($r,'admin/pos/instalments') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18" stroke-linecap="round"/></svg>
            Instalments
        </a>
        <a href="/admin/support" class="{{ str_starts_with($r,'admin/support') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a8 8 0 01-8 8H7l-4 3v-6.2A8 8 0 1113 20" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Support
        </a>
    </div>
</div>

{{-- ===== INVENTORY & OPS ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Operations</div>
    <div class="nav-section-body">
        <a href="/admin/inventory" class="{{ str_starts_with($r,'admin/inventory') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7" stroke-linejoin="round"/></svg>
            Inventory
        </a>
        <a href="/admin/warehouse" class="{{ str_starts_with($r,'admin/warehouse') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Warehouse Map
        </a>
        <a href="/admin/barcode" class="{{ str_starts_with($r,'admin/barcode') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 5h2v14H3zm4 0h1v14H7zm3 0h2v14h-2zm4 0h1v14h-1zm3 0h3v14h-3zM3 5h2m-2 14h2"/></svg>
            Barcode System
        </a>
        <a href="/admin/bom-imports" class="{{ str_starts_with($r,'admin/bom-imports') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z" stroke-linejoin="round"/><path d="M17 16l2 2 3-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            BOM Imports
        </a>
        <a href="/admin/lms" class="{{ str_starts_with($r,'admin/lms') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5z"/></svg>
            LMS
        </a>
        <a href="/admin/pcb" class="{{ str_starts_with($r,'admin/pcb') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4" stroke-linejoin="round"/></svg>
            PCB
        </a>
        <a href="/admin/smd" class="{{ str_starts_with($r,'admin/smd') ? 'active':'' }}">SMD Identification</a>
    </div>
</div>

{{-- ===== NETWORK ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Network</div>
    <div class="nav-section-body">
        <a href="/admin/vendors" class="{{ str_starts_with($r,'admin/vendors') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2" stroke-linecap="round"/><circle cx="9" cy="7" r="4"/></svg>
            Vendors
        </a>
        <a href="/admin/distributors" class="{{ str_starts_with($r,'admin/distributors') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18M6 7v10a2 2 0 002 2h8a2 2 0 002-2V7" stroke-linejoin="round"/></svg>
            Distributors
        </a>
        <a href="/admin/applications" class="{{ str_starts_with($r,'admin/applications') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6v4H9zM5 7h14v14H5z" stroke-linejoin="round"/></svg>
            Applications
        </a>
        <a href="/admin/partner-approvals" class="{{ str_starts_with($r,'admin/partner-approvals') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3 3-2-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Partner Approvals
        </a>
        <a href="/admin/affiliate" class="{{ str_starts_with($r,'admin/affiliate') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="7" cy="7" r="3"/><circle cx="17" cy="17" r="3"/><path d="M14 7h4v4M7 10v4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Affiliate
        </a>
        <a href="/admin/users" class="{{ str_starts_with($r,'admin/users') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0112 0v1" stroke-linecap="round"/></svg>
            Users &amp; Roles
        </a>
    </div>
</div>

{{-- ===== MARKETING ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Marketing</div>
    <div class="nav-section-body">
        @if(auth()->user()?->hasPermission('campaigns.view'))
        <a href="/admin/marketing" class="{{ $r==='admin/marketing' ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-3 3 2 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Marketing &amp; CRM
        </a>
        <a href="/admin/email" class="{{ str_starts_with($r,'admin/email') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email Campaigns
        </a>
        <a href="/admin/email/providers" class="{{ str_starts_with($r,'admin/email/providers') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email Providers
        </a>
        @endif
        @if(auth()->user()?->hasPermission('customers.view'))
        <a href="/admin/marketing/crm" class="{{ str_starts_with($r,'admin/marketing/crm') ? 'active':'' }}">CRM &amp; Segments</a>
        @endif
        @if(auth()->user()?->hasPermission('customers.import'))
        <a href="/admin/marketing/customer-imports" class="{{ str_starts_with($r,'admin/marketing/customer-imports') ? 'active':'' }}">Customer Imports</a>
        @endif
        @if(auth()->user()?->hasPermission('campaigns.view'))
        <a href="/admin/marketing/newsletter" class="{{ str_starts_with($r,'admin/marketing/newsletter') ? 'active':'' }}">Newsletter</a>
        <a href="/admin/marketing/email" class="{{ str_starts_with($r,'admin/marketing/email') ? 'active':'' }}">Email Campaigns</a>
        <a href="/admin/marketing/automation" class="{{ str_starts_with($r,'admin/marketing/automation') ? 'active':'' }}">Automation</a>
        <a href="/admin/marketing/whatsapp" class="{{ str_starts_with($r,'admin/marketing/whatsapp') ? 'active':'' }}">WhatsApp</a>
        <a href="/admin/marketing/analytics" class="{{ str_starts_with($r,'admin/marketing/analytics') ? 'active':'' }}">Analytics</a>
        <a href="/admin/marketing/abandoned-carts" class="{{ str_starts_with($r,'admin/marketing/abandoned-carts') ? 'active':'' }}">Abandoned Carts</a>
        <a href="/admin/marketing/audit" class="{{ str_starts_with($r,'admin/marketing/audit') ? 'active':'' }}">Audit Log</a>
        @endif
        @if(auth()->user()?->hasPermission('email.providers.manage'))
        <a href="/admin/marketing/settings" class="{{ str_starts_with($r,'admin/marketing/settings') ? 'active':'' }}">Communication Settings</a>
        @endif
    </div>
</div>

{{-- ===== EDUCATION & STEM ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Education &amp; STEM</div>
    <div class="nav-section-body">
        <a href="/admin/education" class="{{ $r==='admin/education' ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8" stroke-linejoin="round"/></svg>
            Dashboard
        </a>
        <a href="/admin/education/projects" class="{{ str_starts_with($r,'admin/education/projects') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M3 9h18M8 21h8M12 17v4" stroke-linejoin="round"/></svg>
            Projects
        </a>
        <a href="/admin/education/sensors" class="{{ str_starts_with($r,'admin/education/sensors') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke-linecap="round"/></svg>
            Sensors
        </a>
        <a href="/admin/education/courses" class="{{ str_starts_with($r,'admin/education/courses') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5z"/></svg>
            Courses
        </a>
        <a href="/admin/education/analytics" class="{{ str_starts_with($r,'admin/education/analytics') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-3 3 2 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Analytics
        </a>
    </div>
</div>

{{-- ===== SELLER INTELLIGENCE ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>Seller Intelligence</div>
    <div class="nav-section-body">
        <a href="/admin/seller-intelligence" class="{{ $r==='admin/seller-intelligence' ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-3 3 2 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Dashboard
        </a>
        <a href="/admin/seller-intelligence/trending" class="{{ str_starts_with($r,'admin/seller-intelligence/trending') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 6l-9.5 9.5-5-5L1 18" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Trending MPNs
        </a>
        <a href="/admin/seller-intelligence/unfulfilled" class="{{ str_starts_with($r,'admin/seller-intelligence/unfulfilled') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Unfulfilled Demand
        </a>
        <a href="/admin/seller-intelligence/supply-gaps" class="{{ str_starts_with($r,'admin/seller-intelligence/supply-gaps') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Supply Gaps
        </a>
    </div>
</div>

{{-- ===== SYSTEM ===== --}}
<div class="nav-section">
    <div class="nav-section-header" data-nav-toggle>System</div>
    <div class="nav-section-body">
        <a href="/admin/settings" class="{{ str_starts_with($r,'admin/settings') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/></svg>
            Settings
        </a>
    </div>
</div>

</nav>

<script nonce="{{ $csp_nonce ?? '' }}">
// Toggle nav sections on header click
document.querySelectorAll('[data-nav-toggle]').forEach(function(header) {
    header.addEventListener('click', function() {
        this.parentElement.classList.toggle('collapsed');
    });
});
// Collapse all sections except the one containing the active page
document.querySelectorAll('.nav-section').forEach(function(section) {
    if (section.querySelector('.active')) {
        section.classList.remove('collapsed');
    } else {
        section.classList.add('collapsed');
    }
});
</script>
