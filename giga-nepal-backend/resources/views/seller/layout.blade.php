@extends('portal.shell')
@php
$vendor = $vendor ?? $v ?? null;
$portal = [
    'slug' => 'seller',
    'name' => 'Seller Portal',
    'nav' => [
        // OVERVIEW
        ['group' => 'OVERVIEW', 'icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/seller', 'pattern' => 'seller'],
        ['group' => 'OVERVIEW', 'icon' => 'checklist', 'label' => 'Readiness & Onboarding', 'href' => '/seller/readiness', 'pattern' => 'seller/readiness*'],
        ['group' => 'OVERVIEW', 'icon' => 'bell', 'label' => 'Notifications', 'href' => '/seller/notifications', 'pattern' => 'seller/notifications*'],
        
        // CATALOG
        ['group' => 'CATALOG', 'icon' => 'products', 'label' => 'My Products', 'href' => '/seller/products', 'pattern' => 'seller/products*'],
        ['group' => 'CATALOG', 'icon' => 'add', 'label' => 'Add Product', 'href' => '/seller/products/add', 'pattern' => 'seller/products/add*'],
        ['group' => 'CATALOG', 'icon' => 'search', 'label' => 'Match Existing MPN', 'href' => '/seller/products/match', 'pattern' => 'seller/products/match*'],
        ['group' => 'CATALOG', 'icon' => 'upload', 'label' => 'Bulk Import', 'href' => '/seller/products/import', 'pattern' => 'seller/products/import*'],
        ['group' => 'CATALOG', 'icon' => 'draft', 'label' => 'Drafts', 'href' => '/seller/products/drafts', 'pattern' => 'seller/products/drafts*'],
        ['group' => 'CATALOG', 'icon' => 'reject', 'label' => 'Rejected Products', 'href' => '/seller/products/rejected', 'pattern' => 'seller/products/rejected*'],
        
        // INVENTORY
        ['group' => 'INVENTORY', 'icon' => 'inventory', 'label' => 'Inventory Overview', 'href' => '/seller/inventory', 'pattern' => 'seller/inventory*'],
        ['group' => 'INVENTORY', 'icon' => 'warehouse', 'label' => 'Warehouse Stock', 'href' => '/seller/inventory/warehouse', 'pattern' => 'seller/inventory/warehouse*'],
        ['group' => 'INVENTORY', 'icon' => 'movement', 'label' => 'Stock Movements', 'href' => '/seller/inventory/movements', 'pattern' => 'seller/inventory/movements*'],
        ['group' => 'INVENTORY', 'icon' => 'reserve', 'label' => 'Reservations', 'href' => '/seller/inventory/reservations', 'pattern' => 'seller/inventory/reservations*'],
        ['group' => 'INVENTORY', 'icon' => 'alert', 'label' => 'Low Stock Alerts', 'href' => '/seller/inventory/alerts', 'pattern' => 'seller/inventory/alerts*'],
        ['group' => 'INVENTORY', 'icon' => 'upload', 'label' => 'Inventory Import', 'href' => '/seller/inventory/import', 'pattern' => 'seller/inventory/import*'],
        
        // SALES
        ['group' => 'SALES', 'icon' => 'orders', 'label' => 'Orders', 'href' => '/seller/orders', 'pattern' => 'seller/orders*'],
        ['group' => 'SALES', 'icon' => 'rfq', 'label' => 'RFQs', 'href' => '/seller/rfqs', 'pattern' => 'seller/rfqs*'],
        ['group' => 'SALES', 'icon' => 'quote', 'label' => 'Quotations', 'href' => '/seller/quotations', 'pattern' => 'seller/quotations*'],
        ['group' => 'SALES', 'icon' => 'return', 'label' => 'Returns', 'href' => '/seller/returns', 'pattern' => 'seller/returns*'],
        ['group' => 'SALES', 'icon' => 'cancel', 'label' => 'Cancellations', 'href' => '/seller/cancellations', 'pattern' => 'seller/cancellations*'],
        ['group' => 'SALES', 'icon' => 'message', 'label' => 'Customer Messages', 'href' => '/seller/messages', 'pattern' => 'seller/messages*'],
        
        // LOGISTICS
        ['group' => 'LOGISTICS', 'icon' => 'warehouse', 'label' => 'Warehouses', 'href' => '/seller/warehouses', 'pattern' => 'seller/warehouses*'],
        ['group' => 'LOGISTICS', 'icon' => 'dispatch', 'label' => 'Dispatch', 'href' => '/seller/dispatch', 'pattern' => 'seller/dispatch*'],
        ['group' => 'LOGISTICS', 'icon' => 'shipment', 'label' => 'Shipments', 'href' => '/seller/shipments', 'pattern' => 'seller/shipments*'],
        ['group' => 'LOGISTICS', 'icon' => 'pickup', 'label' => 'Pickup Requests', 'href' => '/seller/pickups', 'pattern' => 'seller/pickups*'],
        ['group' => 'LOGISTICS', 'icon' => 'freight', 'label' => 'Freight', 'href' => '/seller/freight', 'pattern' => 'seller/freight*'],
        ['group' => 'LOGISTICS', 'icon' => 'tracking', 'label' => 'Tracking', 'href' => '/seller/tracking', 'pattern' => 'seller/tracking*'],
        
        // FINANCE
        ['group' => 'FINANCE', 'icon' => 'earnings', 'label' => 'Earnings', 'href' => '/seller/earnings', 'pattern' => 'seller/earnings*'],
        ['group' => 'FINANCE', 'icon' => 'payout', 'label' => 'Payouts', 'href' => '/seller/payouts', 'pattern' => 'seller/payouts*'],
        ['group' => 'FINANCE', 'icon' => 'statement', 'label' => 'Statements', 'href' => '/seller/statements', 'pattern' => 'seller/statements*'],
        ['group' => 'FINANCE', 'icon' => 'commission', 'label' => 'Commissions', 'href' => '/seller/commissions', 'pattern' => 'seller/commissions*'],
        ['group' => 'FINANCE', 'icon' => 'tax', 'label' => 'Taxes & Invoices', 'href' => '/seller/taxes', 'pattern' => 'seller/taxes*'],
        
        // MARKETPLACE
        ['group' => 'MARKETPLACE', 'icon' => 'marketplace', 'label' => 'Marketplace Access', 'href' => '/seller/marketplace', 'pattern' => 'seller/marketplace*'],
        ['group' => 'MARKETPLACE', 'icon' => 'regional', 'label' => 'Regional Pricing', 'href' => '/seller/pricing', 'pattern' => 'seller/pricing*'],
        ['group' => 'MARKETPLACE', 'icon' => 'offer', 'label' => 'Seller Offers', 'href' => '/seller/offers', 'pattern' => 'seller/offers*'],
        ['group' => 'MARKETPLACE', 'icon' => 'performance', 'label' => 'Performance', 'href' => '/seller/performance', 'pattern' => 'seller/performance*'],
        ['group' => 'MARKETPLACE', 'icon' => 'compliance', 'label' => 'Compliance', 'href' => '/seller/compliance', 'pattern' => 'seller/compliance*'],

        // MARKET INTELLIGENCE
        ['group' => 'INTELLIGENCE', 'icon' => 'trending', 'label' => 'Market Intelligence', 'href' => '/seller/intelligence', 'pattern' => 'seller/intelligence'],
        ['group' => 'INTELLIGENCE', 'icon' => 'trending', 'label' => 'Trending MPNs', 'href' => '/seller/intelligence/trending', 'pattern' => 'seller/intelligence/trending*'],
        ['group' => 'INTELLIGENCE', 'icon' => 'demand', 'label' => 'Fast-Selling Categories', 'href' => '/seller/intelligence/categories', 'pattern' => 'seller/intelligence/categories*'],
        ['group' => 'INTELLIGENCE', 'icon' => 'search', 'label' => 'Unmet Demand', 'href' => '/seller/intelligence/unmet', 'pattern' => 'seller/intelligence/unmet*'],
        ['group' => 'INTELLIGENCE', 'icon' => 'stock', 'label' => 'My Product Performance', 'href' => '/seller/intelligence/my-products', 'pattern' => 'seller/intelligence/my-products*'],

        // ANALYTICS
        ['group' => 'ANALYTICS', 'icon' => 'dashboard', 'label' => 'Analytics Dashboard', 'href' => '/seller/analytics', 'pattern' => 'seller/analytics'],
        ['group' => 'ANALYTICS', 'icon' => 'products', 'label' => 'Product Analytics', 'href' => '/seller/analytics/products', 'pattern' => 'seller/analytics/products*'],
        ['group' => 'ANALYTICS', 'icon' => 'engagement', 'label' => 'Customer Engagement', 'href' => '/seller/analytics/engagement', 'pattern' => 'seller/analytics/engagement*'],
        
        // ACCOUNT
        ['group' => 'ACCOUNT', 'icon' => 'profile', 'label' => 'Business Profile', 'href' => '/seller/profile', 'pattern' => 'seller/profile*'],
        ['group' => 'ACCOUNT', 'icon' => 'document', 'label' => 'Documents', 'href' => '/seller/documents', 'pattern' => 'seller/documents*'],
        ['group' => 'ACCOUNT', 'icon' => 'team', 'label' => 'Team Members', 'href' => '/seller/team', 'pattern' => 'seller/team*'],
        ['group' => 'ACCOUNT', 'icon' => 'support', 'label' => 'Support', 'href' => '/seller/support', 'pattern' => 'seller/support*'],
        ['group' => 'ACCOUNT', 'icon' => 'settings', 'label' => 'Settings', 'href' => '/seller/settings', 'pattern' => 'seller/settings*'],
        ['group' => 'ACCOUNT', 'icon' => 'logout', 'label' => 'Log Out', 'href' => '/seller/logout', 'pattern' => 'seller/logout*', 'method' => 'POST'],
    ],
];
@endphp

@php
// Group nav items for proper rendering
$navGroups = [];
$currentGroup = null;
foreach ($portal['nav'] as $idx => $item) {
    $group = $item['group'] ?? null;
    if ($group !== $currentGroup) {
        if ($group) {
            $navGroups[$group] = ['items' => [], 'start_idx' => $idx];
        } else {
            $navGroups['_ungrouped_' . $idx] = ['items' => [$item], 'start_idx' => $idx, 'is_ungrouped' => true];
        }
        $currentGroup = $group;
    } else {
        if (isset($navGroups[$group])) {
            $navGroups[$group]['items'][] = $item;
        } else {
            $lastKey = array_key_last($navGroups);
            $navGroups[$lastKey]['items'][] = $item;
        }
    }
}
$portal = $portal + ['nav_groups' => $navGroups];
@endphp
