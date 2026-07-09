# NeoGiga Admin Dashboard - Next Phase Backlog

## Priority P0 (Critical - Complete Immediately)

### 1. Order List Page (resources/views/admin/orders/index.blade.php)
**Estimated: 4 hours**
- [ ] Filter bar with all options
- [ ] Data table with columns from spec
- [ ] Bulk actions checkbox
- [ ] Pagination component
- [ ] Export buttons placeholder
- [ ] Empty state

### 2. Order Detail Page (resources/views/admin/orders/show.blade.php)
**Estimated: 6 hours**
- [ ] Two-column layout (details + actions)
- [ ] Customer information card
- [ ] Order meta information
- [ ] Product table with images
- [ ] Subtotal/tax/shipping/total breakdown
- [ ] Payment status update form
- [ ] Delivery status update form
- [ ] Tracking information panel
- [ ] Order updates timeline
- [ ] Internal notes section
- [ ] Print/download invoice buttons

### 3. Missing Blade Views
**Estimated: 2 hours**
```bash
touch resources/views/admin/{ai,products,sellers,marketing,system,pos}/{index,show,create,edit}.blade.php
```
Create stub views for all sidebar routes to prevent 500 errors.

## Priority P1 (High - This Week)

### 4. AI Command Center Pages
**Estimated: 8 hours**
- [ ] `admin/ai/conversations.blade.php` - Conversation list with filters
- [ ] `admin/ai/bom.blade.php` - BOM builder admin view
- [ ] `admin/ai/pos.blade.php` - AI POS sessions
- [ ] `admin/ai/logs.blade.php` - Tool call logs table

### 5. Component Library Completion
**Estimated: 6 hours**
Create reusable Blade components in `resources/views/components/admin/`:
- [ ] `<x-admin.stat-card>` 
- [ ] `<x-admin.data-table>`
- [ ] `<x-admin.filter-bar>`
- [ ] `<x-admin.timeline>`
- [ ] `<x-admin.detail-panel>`
- [ ] `<x-admin.empty-state>`
- [ ] `<x-admin.loading-skeleton>`
- [ ] `<x-admin.modal>`
- [ ] `<x-admin.drawer>`

### 6. Queue Monitor Page
**Estimated: 3 hours**
- [ ] Pending jobs count and list
- [ ] Failed jobs with retry button
- [ ] Worker status indicator
- [ ] Last run timestamp
- [ ] Clear failed jobs action

### 7. Permission Integration
**Estimated: 4 hours**
- [ ] Install Spatie Laravel Permission
- [ ] Create admin roles migration
- [ ] Seed default permissions
- [ ] Add @can gates to all destructive actions
- [ ] Create permission middleware

## Priority P2 (Medium - Next Sprint)

### 8. Chart.js Integration
**Estimated: 4 hours**
```bash
npm install chart.js
```
- [ ] Sales trend line chart
- [ ] Orders by category pie chart
- [ ] Inventory by warehouse bar chart
- [ ] AI usage trend chart
- [ ] Campaign performance chart

### 9. Product Management Pages
**Estimated: 12 hours**
- [ ] Global products list with filters
- [ ] Product create/edit form
- [ ] Category tree manager
- [ ] Brand/manufacturer CRUD
- [ ] Attribute/specification manager
- [ ] Datasheet upload interface

### 10. Seller Management
**Estimated: 8 hours**
- [ ] Seller applications review page
- [ ] Approved sellers list
- [ ] Seller detail view
- [ ] Settlement history
- [ ] Performance metrics

### 11. Marketing Module
**Estimated: 10 hours**
- [ ] Campaign list and create
- [ ] Segment builder
- [ ] Email template editor
- [ ] Newsletter subscriber management
- [ ] AI Campaign Builder UI placeholder
- [ ] Delivery logs viewer

### 12. System Settings
**Estimated: 8 hours**
- [ ] Users & Staff management
- [ ] Roles & Permissions editor
- [ ] Country/currency/language settings
- [ ] Payment gateway configuration UI
- [ ] Email provider settings
- [ ] Audit log viewer

## Priority P3 (Low - Future Enhancements)

### 13. Real-time Features
**Estimated: 16 hours**
- [ ] WebSocket integration (Laravel Reverb/Pusher)
- [ ] Live order notifications
- [ ] Real-time dashboard updates
- [ ] Chat support widget

### 14. Advanced Search
**Estimated: 6 hours**
- [ ] Elasticsearch/Algolia integration
- [ ] Global search across all modules
- [ ] Saved searches
- [ ] Search suggestions

### 15. Export/Import
**Estimated: 8 hours**
- [ ] CSV export for all tables
- [ ] PDF invoice generation (DomPDF/Snappy)
- [ ] Bulk import via CSV
- [ ] Scheduled export jobs

### 16. Mobile App Sync
**Estimated: 12 hours**
- [ ] Admin API endpoints
- [ ] Mobile-responsive optimizations
- [ ] PWA manifest
- [ ] Offline mode support

### 17. Analytics Dashboard
**Estimated: 10 hours**
- [ ] Google Analytics integration
- [ ] Custom event tracking
- [ ] Conversion funnels
- [ ] User behavior heatmaps

## Technical Debt

### Refactoring Needed
- [ ] Extract repeated Blade sections into partials
- [ ] Create view composers for common data
- [ ] Implement proper form request validation
- [ ] Add unit tests for controllers
- [ ] Create feature tests for critical flows

### Performance Optimization
- [ ] Cache dashboard metrics (Redis)
- [ ] Lazy load large tables
- [ ] Optimize database queries (N+1 fixes)
- [ ] Add database indexes
- [ ] Enable query caching

### Security Hardening
- [ ] Rate limiting on all POST endpoints
- [ ] CSRF token rotation
- [ ] XSS protection headers
- [ ] SQL injection audit
- [ ] File upload validation

## Documentation Tasks
- [ ] Admin user manual
- [ ] API documentation for mobile sync
- [ ] Deployment guide
- [ ] Troubleshooting guide
- [ ] Video tutorials

## Testing Requirements
- [ ] PHPUnit tests for all controllers
- [ ] Browser tests for critical flows
- [ ] Load testing (100 concurrent admins)
- [ ] Cross-browser testing matrix
- [ ] Mobile device testing

## Success Metrics
- Dashboard loads in <2 seconds
- Order list filters respond in <500ms
- Zero JavaScript errors in production
- 95%+ Lighthouse accessibility score
- Mobile usability score 90%+

---

## Immediate Next Actions (Today)
1. Create order list view (`admin/orders/index.blade.php`)
2. Create order detail view (`admin/orders/show.blade.php`)
3. Create stub views for all sidebar routes
4. Test navigation flow end-to-end
5. Document any schema mismatches

## Blockers
- Need confirmation on actual database schema for orders, products, sellers
- Need Chart.js decision (include or exclude?)
- Need design approval on current color scheme
- Need access to existing admin authentication system

## Dependencies
- Laravel Sanctum (installed)
- Database migrations (existing)
- Admin auth system (existing in giga-nepal-backend)
