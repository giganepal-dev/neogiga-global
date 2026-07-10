# NeoGiga Permission Matrix

## Overview

This document defines the complete role-based access control (RBAC) matrix for NeoGiga. All permissions follow the format: `{domain}.{resource}.{action}`.

## Role Definitions

### System Roles

| Role | Level | Description |
|------|-------|-------------|
| Super Admin | System | Full system access, can manage all tenants and settings |
| Global Admin | Platform | Platform-wide management, cannot modify super admin settings |
| Country Admin | Regional | Manage specific country operations |
| Regional Admin | Regional | Manage multi-country region |

### Operational Roles

| Role | Level | Description |
|------|-------|-------------|
| Warehouse Manager | Operational | Manage warehouse operations and inventory |
| Finance Manager | Operational | Manage accounting, settlements, payouts |
| Product Manager | Operational | Manage product catalogue and approvals |
| SEO Manager | Operational | Manage SEO settings and content |
| Support Agent | Operational | Handle customer support tickets |
| Procurement Buyer | Operational | Manage purchase orders and suppliers |

### External Roles

| Role | Level | Description |
|------|-------|-------------|
| Manufacturer | External | Manufacturer dashboard access |
| Global Distributor | External | Multi-country distributor access |
| Country Distributor | External | Single-country distributor access |
| Regional Distributor | External | Regional distributor access |
| Reseller | External | Reseller dashboard access |
| Local Shop | External | Local shop dashboard access |
| Marketplace Seller | External | Marketplace seller dashboard |
| Seller Staff | External | Limited seller organization access |
| Corporate Buyer | External | B2B buyer with approval workflows |
| Retail Customer | External | Standard customer access |

---

## Permission Groups

### Identity Permissions

| Permission | Description | Super | Global | Country | Regional | Warehouse | Finance | Product | SEO | Support |
|------------|-------------|-------|--------|---------|----------|-----------|---------|---------|-----|---------|
| identity.user.view | View users | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.user.create | Create users | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.user.edit | Edit users | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.user.delete | Delete users | ✓ | ✓ | | | | | | | |
| identity.user.suspend | Suspend users | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.user.impersonate | Impersonate users | ✓ | ✓ | ✓ | | | | | | |
| identity.role.view | View roles | ✓ | ✓ | | | | | | | |
| identity.role.manage | Manage roles | ✓ | | | | | | | | |
| identity.permission.view | View permissions | ✓ | ✓ | | | | | | | |
| identity.organization.view | View organizations | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.organization.verify | Verify organizations | ✓ | ✓ | ✓ | ✓ | | | | | |
| identity.organization.suspend | Suspend organizations | ✓ | ✓ | ✓ | | | | | | |
| identity.session.view | View login sessions | ✓ | ✓ | ✓ | | | | | | |
| identity.session.terminate | Terminate sessions | ✓ | ✓ | ✓ | | | | | | |
| identity.audit.view | View audit logs | ✓ | ✓ | ✓ | | | | | | |

### Marketplace Permissions

| Permission | Description | Super | Global | Country | Regional |
|------------|-------------|-------|--------|---------|----------|
| marketplace.country.view | View countries | ✓ | ✓ | ✓ | ✓ |
| marketplace.country.manage | Manage countries | ✓ | ✓ | | |
| marketplace.storefront.view | View storefronts | ✓ | ✓ | ✓ | ✓ |
| marketplace.storefront.manage | Manage storefronts | ✓ | ✓ | ✓ | |
| marketplace.currency.view | View currencies | ✓ | ✓ | ✓ | ✓ |
| marketplace.currency.manage | Manage currencies | ✓ | ✓ | | |
| marketplace.tax.view | View tax rates | ✓ | ✓ | ✓ | ✓ |
| marketplace.tax.manage | Manage tax rates | ✓ | ✓ | ✓ | |
| marketplace.import_duty.view | View import duties | ✓ | ✓ | ✓ | ✓ |
| marketplace.import_duty.manage | Manage import duties | ✓ | ✓ | ✓ | |
| marketplace.payment_gateway.view | View payment gateways | ✓ | ✓ | ✓ | ✓ |
| marketplace.payment_gateway.manage | Manage payment gateways | ✓ | ✓ | | |
| marketplace.shipping.view | View shipping methods | ✓ | ✓ | ✓ | ✓ |
| marketplace.shipping.manage | Manage shipping methods | ✓ | ✓ | ✓ | |

### Catalogue Permissions

| Permission | Description | Super | Global | Product |
|------------|-------------|-------|--------|---------|
| catalogue.category.view | View categories | ✓ | ✓ | ✓ |
| catalogue.category.create | Create categories | ✓ | ✓ | ✓ |
| catalogue.category.edit | Edit categories | ✓ | ✓ | ✓ |
| catalogue.category.delete | Delete categories | ✓ | ✓ | |
| catalogue.brand.view | View brands | ✓ | ✓ | ✓ |
| catalogue.brand.manage | Manage brands | ✓ | ✓ | ✓ |
| catalogue.manufacturer.view | View manufacturers | ✓ | ✓ | ✓ |
| catalogue.manufacturer.verify | Verify manufacturers | ✓ | ✓ | ✓ |
| catalogue.attribute.view | View attributes | ✓ | ✓ | ✓ |
| catalogue.attribute.manage | Manage attributes | ✓ | ✓ | ✓ |

### Product Permissions

| Permission | Description | Super | Global | Product | Seller |
|------------|-------------|-------|--------|---------|--------|
| product.view | View products | ✓ | ✓ | ✓ | ✓ |
| product.create | Create products | ✓ | ✓ | ✓ | ✓* |
| product.edit | Edit products | ✓ | ✓ | ✓ | ✓* |
| product.delete | Delete products | ✓ | ✓ | ✓ | ✓* |
| product.approve | Approve products | ✓ | ✓ | ✓ | |
| product.reject | Reject products | ✓ | ✓ | ✓ | |
| product.publish | Publish to country | ✓ | ✓ | ✓ | ✓* |
| product.unpublish | Unpublish from country | ✓ | ✓ | ✓ | ✓* |
| product.import | Import products | ✓ | ✓ | ✓ | ✓ |
| product.export | Export products | ✓ | ✓ | ✓ | ✓ |
| product.merge | Merge duplicate products | ✓ | ✓ | ✓ | |
| product.lifecycle.manage | Manage lifecycle status | ✓ | ✓ | ✓ | |

*Sellers can only manage their own products/offers

### Seller Permissions

| Permission | Description | Super | Global | Country | Seller |
|------------|-------------|-------|--------|---------|--------|
| seller.view | View sellers | ✓ | ✓ | ✓ | |
| seller.application.view | View applications | ✓ | ✓ | ✓ | |
| seller.application.approve | Approve applications | ✓ | ✓ | ✓ | |
| seller.application.reject | Reject applications | ✓ | ✓ | ✓ | |
| seller.suspend | Suspend sellers | ✓ | ✓ | ✓ | |
| seller.performance.view | View performance metrics | ✓ | ✓ | ✓ | ✓* |
| seller.settlement.view | View settlements | ✓ | ✓ | ✓ | ✓* |
| seller.payout.approve | Approve payouts | ✓ | ✓ | ✓ | |
| seller.commission.manage | Manage commissions | ✓ | ✓ | | |
| seller.staff.manage | Manage seller staff | ✓ | ✓ | | ✓* |

*Sellers can only view their own data

### Inventory Permissions

| Permission | Description | Super | Global | Warehouse | Seller |
|------------|-------------|-------|--------|-----------|--------|
| inventory.warehouse.view | View warehouses | ✓ | ✓ | ✓ | ✓* |
| inventory.warehouse.manage | Manage warehouses | ✓ | ✓ | ✓ | |
| inventory.location.view | View locations | ✓ | ✓ | ✓ | ✓* |
| inventory.location.manage | Manage locations | ✓ | ✓ | ✓ | |
| inventory.stock.view | View stock levels | ✓ | ✓ | ✓ | ✓* |
| inventory.stock.adjust | Adjust stock | ✓ | ✓ | ✓ | ✓* |
| inventory.stock.transfer | Transfer stock | ✓ | ✓ | ✓ | |
| inventory.movement.view | View movements | ✓ | ✓ | ✓ | ✓* |
| inventory.reservation.view | View reservations | ✓ | ✓ | ✓ | ✓* |
| inventory.reservation.cancel | Cancel reservations | ✓ | ✓ | ✓ | |
| inventory.batch.view | View batches | ✓ | ✓ | ✓ | ✓* |
| inventory.batch.manage | Manage batches | ✓ | ✓ | ✓ | |
| inventory.count.perform | Perform stock count | ✓ | ✓ | ✓ | |
| inventory.reconciliation.perform | Perform reconciliation | ✓ | ✓ | ✓ | |

*Sellers can only view their own inventory

### Order Permissions

| Permission | Description | Super | Global | Country | Seller | Customer |
|------------|-------------|-------|--------|---------|--------|----------|
| order.view | View orders | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.create | Create orders | ✓ | ✓ | ✓ | | ✓ |
| order.edit | Edit orders | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.cancel | Cancel orders | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.refund.view | View refunds | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.refund.process | Process refunds | ✓ | ✓ | ✓ | | |
| order.return.view | View returns | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.return.approve | Approve returns | ✓ | ✓ | ✓ | | |
| order.shipment.view | View shipments | ✓ | ✓ | ✓ | ✓* | ✓* |
| order.shipment.create | Create shipments | ✓ | ✓ | ✓ | ✓* | |

*Sellers/Customers can only view their own orders

### RFQ & Quotation Permissions

| Permission | Description | Super | Global | Buyer | Seller |
|------------|-------------|-------|--------|-------|--------|
| rfq.view | View RFQs | ✓ | ✓ | ✓* | ✓* |
| rfq.create | Create RFQs | ✓ | ✓ | ✓ | |
| rfq.edit | Edit RFQs | ✓ | ✓ | ✓* | |
| rfq.invite | Invite sellers to RFQ | ✓ | ✓ | ✓ | |
| rfq.respond | Respond to RFQ | | | | ✓ |
| quotation.view | View quotations | ✓ | ✓ | ✓* | ✓* |
| quotation.create | Create quotations | ✓ | ✓ | | ✓ |
| quotation.revise | Revise quotations | ✓ | ✓ | | ✓ |
| quotation.accept | Accept quotations | ✓ | ✓ | ✓ | |
| quotation.decline | Decline quotations | ✓ | ✓ | ✓ | |

*Users can only view RFQs/quotations they're involved in

### Accounting Permissions

| Permission | Description | Super | Global | Finance |
|------------|-------------|-------|--------|---------|
| accounting.purchase.view | View purchases | ✓ | ✓ | ✓ |
| accounting.purchase.create | Create purchases | ✓ | ✓ | ✓ |
| accounting.sales.view | View sales | ✓ | ✓ | ✓ |
| accounting.invoice.view | View invoices | ✓ | ✓ | ✓ |
| accounting.invoice.create | Create invoices | ✓ | ✓ | ✓ |
| accounting.bill.view | View bills | ✓ | ✓ | ✓ |
| accounting.journal.view | View journal entries | ✓ | ✓ | ✓ |
| accounting.journal.create | Create journal entries | ✓ | ✓ | ✓ |
| accounting.report.view | View financial reports | ✓ | ✓ | ✓ |
| accounting.profitability.view | View profitability | ✓ | ✓ | ✓ |
| accounting.tax.view | View tax reports | ✓ | ✓ | ✓ |
| accounting.period.manage | Manage accounting periods | ✓ | ✓ | ✓ |

### Settlement Permissions

| Permission | Description | Super | Global | Finance | Seller |
|------------|-------------|-------|--------|---------|--------|
| settlement.view | View settlements | ✓ | ✓ | ✓ | ✓* |
| settlement.generate | Generate settlements | ✓ | ✓ | ✓ | |
| settlement.approve | Approve settlements | ✓ | ✓ | ✓ | |
| settlement.payout.view | View payouts | ✓ | ✓ | ✓ | ✓* |
| settlement.payout.request | Request payout | | | | ✓ |
| settlement.payout.approve | Approve payouts | ✓ | ✓ | ✓ | |
| settlement.dispute.view | View disputes | ✓ | ✓ | ✓ | ✓* |
| settlement.dispute.resolve | Resolve disputes | ✓ | ✓ | ✓ | |
| commission.rule.view | View commission rules | ✓ | ✓ | ✓ | ✓* |
| commission.rule.manage | Manage commission rules | ✓ | ✓ | | |

*Sellers can only view their own settlements

### Support Permissions

| Permission | Description | Super | Global | Support | Seller | Customer |
|------------|-------------|-------|--------|---------|--------|----------|
| support.ticket.view | View tickets | ✓ | ✓ | ✓ | ✓* | ✓* |
| support.ticket.create | Create tickets | ✓ | ✓ | ✓ | ✓ | ✓ |
| support.ticket.assign | Assign tickets | ✓ | ✓ | ✓ | | |
| support.ticket.reply | Reply to tickets | ✓ | ✓ | ✓ | ✓* | ✓* |
| support.ticket.resolve | Resolve tickets | ✓ | ✓ | ✓ | | |
| support.ticket.escalate | Escalate tickets | ✓ | ✓ | ✓ | | |
| support.sla.view | View SLA metrics | ✓ | ✓ | ✓ | | |
| support.canned.manage | Manage canned responses | ✓ | ✓ | ✓ | | |
| support.category.manage | Manage categories | ✓ | ✓ | ✓ | | |

*Sellers/Customers can only view their own tickets

### Workflow Permissions

| Permission | Description | Super | Global | Manager |
|------------|-------------|-------|--------|---------|
| workflow.definition.view | View workflow definitions | ✓ | ✓ | ✓ |
| workflow.definition.manage | Manage workflow definitions | ✓ | ✓ | |
| workflow.instance.view | View workflow instances | ✓ | ✓ | ✓ |
| workflow.approve | Approve workflow steps | ✓ | ✓ | ✓ |
| workflow.reject | Reject workflow steps | ✓ | ✓ | ✓ |
| workflow.comment | Add comments | ✓ | ✓ | ✓ |
| workflow.escalate | Escalate workflows | ✓ | ✓ | ✓ |

### SEO & Content Permissions

| Permission | Description | Super | Global | SEO | Product |
|------------|-------------|-------|--------|-----|---------|
| seo.metadata.view | View SEO metadata | ✓ | ✓ | ✓ | ✓ |
| seo.metadata.manage | Manage SEO metadata | ✓ | ✓ | ✓ | ✓ |
| seo.redirect.view | View redirects | ✓ | ✓ | ✓ | |
| seo.redirect.manage | Manage redirects | ✓ | ✓ | ✓ | |
| seo.sitemap.view | View sitemaps | ✓ | ✓ | ✓ | |
| seo.sitemap.generate | Generate sitemaps | ✓ | ✓ | ✓ | |
| content.page.view | View pages | ✓ | ✓ | ✓ | |
| content.page.manage | Manage pages | ✓ | ✓ | ✓ | |
| content.blog.view | View blog posts | ✓ | ✓ | ✓ | |
| content.blog.manage | Manage blog posts | ✓ | ✓ | ✓ | |
| content.menu.manage | Manage menus | ✓ | ✓ | ✓ | |

### Analytics Permissions

| Permission | Description | Super | Global | Finance | Manager |
|------------|-------------|-------|--------|---------|---------|
| analytics.dashboard.view | View dashboards | ✓ | ✓ | ✓ | ✓ |
| analytics.report.view | View reports | ✓ | ✓ | ✓ | ✓ |
| analytics.report.create | Create custom reports | ✓ | ✓ | ✓ | |
| analytics.export | Export analytics data | ✓ | ✓ | ✓ | |
| analytics.realtime.view | View real-time metrics | ✓ | ✓ | ✓ | |

### Risk Intelligence Permissions

| Permission | Description | Super | Global | Manager |
|------------|-------------|-------|--------|---------|
| risk.dashboard.view | View risk dashboard | ✓ | ✓ | ✓ |
| risk.assessment.view | View risk assessments | ✓ | ✓ | ✓ |
| risk.assessment.create | Create risk assessments | ✓ | ✓ | ✓ |
| risk.alert.view | View risk alerts | ✓ | ✓ | ✓ |
| risk.alert.resolve | Resolve risk alerts | ✓ | ✓ | ✓ |
| risk.supplier.view | View supplier risks | ✓ | ✓ | ✓ |
| risk.mitigation.plan | Create mitigation plans | ✓ | ✓ | ✓ |

### BOM Tool Permissions

| Permission | Description | Super | Global | Buyer | Seller |
|------------|-------------|-------|--------|-------|--------|
| bom.upload | Upload BOM files | ✓ | ✓ | ✓ | ✓ |
| bom.view | View BOM projects | ✓ | ✓ | ✓* | ✓* |
| bom.analyze | Analyze BOMs | ✓ | ✓ | ✓ | ✓ |
| bom.quote | Request BOM quotation | ✓ | ✓ | ✓ | |
| bom.export | Export BOM results | ✓ | ✓ | ✓ | ✓ |
| bom.share | Share BOM with team | ✓ | ✓ | ✓ | |

*Users can only view their own BOMs

### AI Commerce Permissions

| Permission | Description | Super | Global | Manager |
|------------|-------------|-------|--------|---------|
| ai.recommendation.view | View AI recommendations | ✓ | ✓ | ✓ |
| ai.substitution.view | View AI substitutions | ✓ | ✓ | ✓ |
| ai.substitution.approve | Approve AI substitutions | ✓ | ✓ | ✓ |
| ai.insight.view | View AI insights | ✓ | ✓ | ✓ |
| ai.conversation.view | View AI conversations | ✓ | ✓ | ✓ |
| ai.model.manage | Manage AI models | ✓ | | |

### Mini-Site Permissions

| Permission | Description | Super | Global | Seller |
|------------|-------------|-------|--------|--------|
| minisite.view | View mini-sites | ✓ | ✓ | ✓* |
| minisite.claim | Claim mini-site | | | ✓ |
| minisite.edit | Edit mini-site content | ✓ | ✓ | ✓* |
| minisite.submit_approval | Submit for approval | | | ✓ |
| minisite.approve | Approve mini-sites | ✓ | ✓ | |

*Sellers can only manage their own mini-site

---

## Permission Implementation Guidelines

### Policy Structure

Every model must have a corresponding policy:

```php
class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('product.view') 
            || $this->isOwner($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('product.create');
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasPermission('product.edit')) {
            return true;
        }
        
        return $this->isOwner($user, $product);
    }

    public function approve(User $user, Product $product): bool
    {
        return $user->hasPermission('product.approve');
    }

    private function isOwner(User $user, Product $product): bool
    {
        // Check if user's organization owns this product
        return $user->organizations()
            ->where('id', $product->owner_id)
            ->exists();
    }
}
```

### Middleware Usage

```php
// Route protection
Route::middleware(['permission:product.create'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
});

// Controller protection
public function __construct()
{
    $this->middleware('permission:seller.settlement.view')->only(['index', 'show']);
    $this->middleware('permission:seller.payout.approve')->only(['approvePayout']);
}
```

### Gate Definitions

```php
// In AuthServiceProvider
Gate::define('manage-country', function (User $user, Country $country) {
    return $user->hasPermission('marketplace.country.manage')
        || $user->countryAdminFor($country->id);
});

Gate::define('view-settlement', function (User $user, Settlement $settlement) {
    if ($user->hasPermission('settlement.view')) {
        return true;
    }
    
    return $settlement->seller->organization->users()->where('user_id', $user->id)->exists();
});
```

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-01-XX | NeoGiga Team | Initial permission matrix |
