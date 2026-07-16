<?php

/*
|--------------------------------------------------------------------------
| NeoGiga icon registry (single source of truth)
|--------------------------------------------------------------------------
|
| One consistent icon set — Lucide (https://lucide.dev, ISC licensed) outline
| style — embedded as SVG path markup so nothing depends on a runtime CDN.
|
| Every entry is the INNER markup of a 24x24 viewBox SVG. The <x-icon>
| component wraps it with the shared attributes (size, stroke-width=currentColor,
| fill=none, round caps/joins, aria). Do NOT paste raw <svg> into templates —
| add the icon here and reference it by name so the whole platform stays
| consistent (stroke width, alignment, size).
|
| Naming is by MEANING, not shape, so intent is obvious at call sites, e.g.
| <x-icon name="cart" />, <x-icon name="rfq" />.
*/

return [

    // Base geometry every icon shares.
    'viewbox' => '0 0 24 24',

    'map' => [

        // ---- Commerce / header actions ---------------------------------
        'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.5 3h2l2.6 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21.5 7H6"/>',
        'wishlist' => '<path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1l1.7 1.7L12 21.4l7.1-7.1 1.7-1.7a5 5 0 0 0 0-7z"/>',
        'compare' => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M12 2v20"/>',
        'buy-now' => '<path d="M13 2 4.1 13.3A1 1 0 0 0 5 15h6l-1 7 8.9-11.3A1 1 0 0 0 18 9h-6z"/>',
        'orders' => '<path d="M16.5 9.4 7.5 4.2M3.3 7 12 12l8.7-5M12 22V12"/><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
        'payments' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'shipping' => '<path d="M14 18V6a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h1"/><path d="M14 9h4l3 3v5a1 1 0 0 1-1 1h-1"/><circle cx="7.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/>',

        // ---- Auth / account --------------------------------------------
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
        'logout' => '<path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4M16 17l5-5-5-5M21 12H9"/>',
        'register' => '<circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M22 11h-6"/>',
        'email' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'password' => '<rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'show-password' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'hide-password' => '<path d="M9.9 4.2A9.8 9.8 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-2.2 2.9M6.6 6.6A13.2 13.2 0 0 0 2 11s3.5 7 10 7a9.8 9.8 0 0 0 4.1-.9M3 3l18 18"/>',
        'two-factor' => '<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/>',

        // ---- Search / AI ------------------------------------------------
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'ai-search' => '<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
        'ai-engineer' => '<rect x="4" y="8" width="16" height="12" rx="2"/><path d="M12 8V4M9 2h6M9 14h.01M15 14h.01M2 13h2M20 13h2"/>',

        // ---- Localisation ----------------------------------------------
        'country' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 2.6 15.4 0 18M12 3c-2.6 2.6-2.6 15.4 0 18"/>',
        'language' => '<path d="M4 5h9M8 3v2c0 5-2.4 8-5 9M6 9c0 3 2.5 5.5 6 6M14 21l4-9 4 9M15.5 18h5"/>',
        'currency' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5A2.5 2.5 0 0 0 12.5 8h-1a2 2 0 0 0 0 4h1a2 2 0 0 1 0 4h-1A2.5 2.5 0 0 1 9 14.5"/>',

        // ---- Comms -----------------------------------------------------
        'notifications' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
        'messages' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'seller-chat' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/>',
        'support' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m4.9 4.9 4.2 4.2M14.9 14.9l4.2 4.2M14.9 9.1l4.2-4.2M4.9 19.1l4.2-4.2"/>',

        // ---- Engineering (BOM / PCB / RFQ) -----------------------------
        'rfq' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
        'bom' => '<path d="M11 4h10M11 9h10M11 15h10M11 20h10"/><path d="m3 4 1.5 1.5L7 3M3 14l1.5 1.5L7 13"/>',
        'add-to-bom' => '<path d="M11 4h10M11 9h7M11 15h10M11 20h7"/><path d="M6 13v6M3 16h6"/>',
        'paste' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
        'match' => '<circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M6 21V9a9 9 0 0 0 9 9"/>',
        'alternatives' => '<path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>',
        'pcb' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 4v4M15 4v4M9 20v-4M15 20v-4M4 9h4M4 15h4M20 9h-4M20 15h-4"/><circle cx="12" cy="12" r="2"/>',
        'pcb-design' => '<path d="M12 19l7-7 3 3-7 7-3-3zM18 13l-1.5-7.5L2 2l3.5 14.5L13 18zM2 2l7.6 7.6M11 13a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
        'gerber' => '<path d="m12 2 9 5-9 5-9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>',
        'fabrication' => '<path d="M2 20h20M4 20V9l5 4V9l5 4V9l5 4v7M9 20v-4h6v4"/>',
        'pcba' => '<rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6v6H9z"/><path d="M9 2v3M15 2v3M9 22v-3M15 22v-3M2 9h3M2 15h3M22 9h-3M22 15h-3"/>',
        'dfm' => '<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6z"/><path d="M8 12h8M12 8v8"/>',
        'quality' => '<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'tracking' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'quote-compare' => '<rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="16" rx="1"/>',

        // ---- Product page ----------------------------------------------
        'datasheet' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9h2"/>',
        'cad' => '<path d="M12 19l7-7 3 3-7 7-3-3zM18 13l-1.5-7.5L2 2l3.5 14.5L13 18zM2 2l7.6 7.6"/><circle cx="11" cy="11" r="2"/>',
        'warranty' => '<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'local-stock' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'global-stock' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 2.6 15.4 0 18M12 3c-2.6 2.6-2.6 15.4 0 18"/>',
        'tutorial' => '<path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/>',
        'reviews' => '<path d="m12 3 2.9 5.9 6.1.9-4.5 4.3 1.1 6-5.6-2.9L6.4 20l1.1-6-4.5-4.3 6.1-.9z"/>',
        'qa' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9.5 9a2.5 2.5 0 0 1 4.6 1.3c0 1.7-2.5 2.2-2.5 2.2M12 15h.01"/>',

        // ---- Admin nav sections ----------------------------------------
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/><rect x="3" y="15" width="7" height="6" rx="1"/>',
        'catalog' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'products' => '<path d="m21 8-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8"/><path d="m3 8 9 5 9-5"/>',
        'categories' => '<path d="M3 6h18M3 12h18M3 18h12"/>',
        'brands' => '<path d="M20.6 13.4 13 21a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 2.6 12.6L10 5a2 2 0 0 1 1.4-.6H19a2 2 0 0 1 2 2v7.4a2 2 0 0 1-.4 1.2z"/><circle cx="16" cy="8" r="1.2"/>',
        'manufacturers' => '<path d="M2 20h20M4 20V9l6 4V9l6 4V9l4 3v4M9 20v-4h6v4"/>',
        'sellers' => '<path d="M3 9 4.5 4h15L21 9M4 9v11h16V9M4 9h16M9 20v-6h6v6"/>',
        'warehouses' => '<path d="M22 8.3V21H2V8.3l10-5.3zM6 21v-9h12v9M9 21v-4h6v4"/>',
        'inventory' => '<path d="M2.97 6.6 12 11l9.03-4.4M12 11v10M4 7.2v9.6l8 4 8-4V7.2l-8-4z"/>',
        'crm' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M17 4.5a3.5 3.5 0 0 1 0 7M22 20a7 7 0 0 0-5-6.7"/>',
        'marketing' => '<path d="M3 11v3a1 1 0 0 0 1 1h3l4 4V6L7 10H4a1 1 0 0 0-1 1zM15.5 9a3 3 0 0 1 0 6M18 6a7 7 0 0 1 0 12"/>',
        'seo' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 11h6M11 8v6"/>',
        'media' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5-4 4-2-2-5 5"/>',
        'lms' => '<path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5M22 10v6"/>',
        'analytics' => '<path d="M3 3v18h18"/><path d="M7 15l3-4 3 2 4-6"/>',
        'accounting' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4M15 14h1M8 18h4"/>',
        'system' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9zM9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1A2 2 0 1 1 4.2 17l.1-.1a1.7 1.7 0 0 0-1.2-2.9H3a2 2 0 0 1 0-4h.2A1.7 1.7 0 0 0 4.4 7l-.1-.1A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 2.9-1.2V3a2 2 0 0 1 4 0v.2A1.7 1.7 0 0 0 17 4.4l.1-.1A2 2 0 1 1 19.9 7l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.4 1z"/>',
        'projects' => '<path d="M4 4h6l2 2h8a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 13h2v4H8zM14 11h2v6h-2z"/>',

        // ---- Table / row actions ---------------------------------------
        'view' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'delete' => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6M10 11v6M14 11v6"/>',
        'duplicate' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'archive' => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8M10 12h4"/>',
        'approve' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5L16 9"/>',
        'reject' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
        'print' => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/>',
        'history' => '<path d="M3 3v6h6"/><path d="M3.5 9a9 9 0 1 0 2-3.4L3 9"/><path d="M12 8v4l3 2"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',

        // ---- Data ops --------------------------------------------------
        'upload' => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 15V3M7 8l5-5 5 5"/>',
        'download' => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 3v12M7 10l5 5 5-5"/>',
        'import' => '<path d="M12 3v12M8 11l4 4 4-4M4 21h16"/>',
        'export' => '<path d="M12 15V3M8 7l4-4 4 4M4 21h16"/>',
        'refresh' => '<path d="M21 12a9 9 0 1 1-2.6-6.4M21 4v5h-5"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'sort' => '<path d="M8 4v16M8 20l-3-3M8 4l3 3M16 20V4M16 4l3 3M16 20l-3-3"/>',

        // ---- Navigation / chrome ---------------------------------------
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'home' => '<path d="m3 11 9-8 9 8M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>',
        'back' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'forward' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'expand' => '<path d="m6 9 6 6 6-6"/>',
        'collapse' => '<path d="m18 15-6-6-6 6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'external' => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.6 1.3c0 1.7-2.6 2.2-2.6 2.2M12 17h.01"/>',

        // ---- Form field affordances ------------------------------------
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>',
        'shield' => '<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6z"/>',
        'star' => '<path d="m12 3 2.9 5.9 6.1.9-4.5 4.3 1.1 6-5.6-2.9L6.4 20l1.1-6-4.5-4.3 6.1-.9z"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
        'inbox' => '<path d="M22 12v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7M5.5 12h13M2 6l2-3h16l2 3M8 6V3h8v3"/>',
    ],
];
