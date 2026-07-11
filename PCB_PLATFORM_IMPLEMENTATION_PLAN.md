# PCB Platform Implementation Plan

## Executive Summary

This document provides a detailed, phase-by-phase implementation plan for building pcb.neogiga.com as an integrated NeoGiga platform. The plan prioritizes security, reuses existing modules, and follows incremental delivery with safety checks at each stage.

**Document Version:** 1.0  
**Created:** 2024-07-11  
**Total Estimated Duration:** 41-56 weeks  
**Stage 1 MVP Duration:** 8-10 weeks  

---

## Implementation Principles

### Guiding Principles

1. **Security First** - Private file protection before any upload functionality
2. **Reuse Before Build** - Leverage 84% existing NeoGiga functionality
3. **Additive Changes Only** - No destructive migrations, no data loss
4. **Manual Fallback** - Human review where automation is uncertain
5. **Incremental Delivery** - Working features at each stage
6. **Test Before Deploy** - No deployment without passing tests
7. **Preserve Existing** - All NeoGiga features must continue working

### Safety Constraints

- ❌ No second user database
- ❌ No duplicated product catalog
- ❌ No disconnected orders or payments
- ❌ No public access to PCB files
- ❌ No AI training on customer files without consent
- ❌ No invented pricing or capabilities
- ❌ No JLCPCB code/branding copying
- ✅ Single sign-on across all platforms
- ✅ Shared PostgreSQL database
- ✅ Integrated cart/order system
- ✅ Unified accounting

---

## Stage 1: Foundation & Core Workspace (Weeks 1-10)

### Objective
Establish pcb.neogiga.com infrastructure, shared authentication, secure file storage, PCB project workspace, basic Gerber upload, and manual quote workflow.

### Deliverables
- ✅ Subdomain configured with SSL
- ✅ Shared authentication (SSO)
- ✅ PCB project workspace (backend + frontend)
- ✅ Private file storage with security controls
- ✅ Gerber ZIP upload foundation
- ✅ Quote configurator UI shell
- ✅ Manual engineering quote workflow
- ✅ BOM/CPL database extensions
- ✅ Admin PCB dashboard foundation
- ✅ Public landing pages (homepage, services)
- ✅ Basic SEO (metadata, noindex for private pages)
- ✅ Queue configuration

### Week 1: Infrastructure & Authentication

#### Tasks

**Day 1-2: Domain Configuration**
- [ ] Add DNS A record for pcb.neogiga.com
- [ ] Configure Nginx virtual host
- [ ] Obtain SSL certificate (Let's Encrypt)
- [ ] Configure HTTPS redirect
- [ ] Test SSL with SSL Labs

**Day 2-3: Session & CORS Configuration**
- [ ] Update `.env`: `SESSION_DOMAIN=.neogiga.com`
- [ ] Update `.env`: `COOKIE_DOMAIN=.neogiga.com`
- [ ] Update `config/cors.php` with PCB origins
- [ ] Update `config/session.php` domain settings
- [ ] Test cross-subdomain login

**Day 3-5: Authentication Extensions**
- [ ] Create seeder for PCB roles (pcb_designer, dfm_engineer, etc.)
- [ ] Create seeder for PCB permissions (25+ permissions)
- [ ] Run seeders in development
- [ ] Test role assignment
- [ ] Test permission checks

**Deliverables:**
- ✅ pcb.neogiga.com resolves with valid SSL
- ✅ Login on neogiga.com works on pcb.neogiga.com
- ✅ PCB roles and permissions available

---

### Week 2: Database Schema - Core Tables

#### Tasks

**Day 1-2: PCB Projects Migration**
```bash
php artisan make:migration create_pcb_projects_table
php artisan make:migration create_pcb_project_members_table
php artisan make:migration create_pcb_project_versions_table
```

**Fields to Implement:**
- uuid, user_id, organization_id, marketplace_id
- project_name, project_code, description
- application_type, confidentiality_level
- prototype_or_production, target_quantity, target_budget
- currency, required_date, destination_country
- shipping_postal_code, preferred_region
- preferred_manufacturer_id, preferred_warehouse_id
- assigned_engineer_id, status (enum), current_version
- timestamps, soft deletes

**Day 3-4: File Security Tables**
```bash
php artisan make:migration create_pcb_files_table
php artisan make:migration create_pcb_file_versions_table
php artisan make:migration create_pcb_file_shares_table
php artisan make:migration create_pcb_file_access_logs_table
php artisan make:migration create_pcb_file_scan_results_table
php artisan make:migration create_pcb_file_retention_policies_table
```

**Day 5: BOM Extensions**
```bash
php artisan make:migration add_cpl_fields_to_bom_tables
```

**Migration Fields:**
- bom_lines: footprint, reference_designators, side, dnp, x/y coordinates, rotation
- bom_projects: pcb_project_id, cpl_file_id, assembly_required, turnkey_type

**Testing:**
- [ ] Run migrations in development
- [ ] Verify foreign key constraints
- [ ] Test soft deletes
- [ ] Verify indexes created

**Deliverables:**
- ✅ All Stage 1 database tables created
- ✅ Migrations reversible (can rollback)
- ✅ Indexes verified

---

### Week 3: Private File Storage

#### Tasks

**Day 1-2: Storage Configuration**
- [ ] Add `pcb-private` disk in `config/filesystems.php`
- [ ] Configure private storage path: `/storage/pcb-private`
- [ ] Set directory permissions to 0700
- [ ] If S3: configure bucket with block public access
- [ ] Enable server-side encryption

**Day 2-3: File Upload Service**
```bash
php artisan make:model PcbFile
php artisan make:model PcbFileShare
php artisan make:model PcbFileAccessLog
php artisan make:model PcbFileScanResult
php artisan make:service PcbFileService
```

**Implement:**
- UUID filename generation
- SHA-256 hash calculation
- MIME type detection (file signature)
- Size validation
- Path traversal prevention
- Storage location abstraction

**Day 4: Authorization Middleware**
```bash
php artisan make:middleware AuthorizePcbFileAccess
```

**Implement:**
- File owner check
- Organization membership check
- Share expiry validation
- Supplier RFQ access check
- Admin override logging
- Access attempt logging

**Day 5: Signed URL Generation**
- Implement temporary signed URLs (15-minute expiry)
- Add download rate limiting
- Implement access count limits per URL
- Log all URL generations

**Testing:**
- [ ] Upload test file, verify stored with UUID name
- [ ] Attempt direct URL access (should fail)
- [ ] Generate signed URL, verify download works
- [ ] Test cross-org access denial
- [ ] Verify access logs created

**Deliverables:**
- ✅ Private storage configured
- ✅ Files stored with UUID names
- ✅ Authorization middleware working
- ✅ Signed URLs expire correctly
- ✅ Access logged comprehensively

---

### Week 4: Gerber Upload Foundation

#### Tasks

**Day 1-2: Upload Endpoint**
```bash
php artisan make:controller Api/Pcb/GerberUploadController
php artisan make:job ProcessGerberUpload
php artisan make:job ScanPcbFileForMalware
```

**Implement:**
- Drag-drop frontend component
- Multi-file upload support
- ZIP bundle handling
- Progress indicator
- Client-side validation

**Day 3-4: Server-Side Processing**
- Validate file extensions against whitelist
- Check MIME types (magic numbers)
- Calculate file hash
- Detect ZIP bombs (compression ratio > 1000:1)
- Extract ZIP archives safely
- Queue malware scan job
- Store files to private disk

**Day 5: Layer Detection (Basic)**
- Parse Gerber file headers
- Identify common layer naming conventions:
  - `.gtl` → Top Copper
  - `.gbl` → Bottom Copper
  - `.gts` → Top Solder Mask
  - `.gbs` → Bottom Solder Mask
  - `.gto` → Top Silkscreen
  - `.gbo` → Bottom Silkscreen
  - `.drl` → Drill File
- Store detected layers in database
- Flag unrecognized files for manual review

**Testing:**
- [ ] Upload single Gerber file
- [ ] Upload ZIP bundle
- [ ] Test oversized file rejection
- [ ] Test invalid extension rejection
- [ ] Verify files stored privately
- [ ] Confirm layer detection accuracy

**Deliverables:**
- ✅ Gerber upload endpoint functional
- ✅ ZIP extraction working safely
- ✅ Basic layer detection operational
- ✅ Malware scan queued
- ✅ Upload progress visible

---

### Week 5-6: PCB Project Workspace (Backend)

#### Tasks

**Week 5 Day 1-2: Project Model & Relationships**
```bash
php artisan make:model PcbProject
php artisan make:model PcbProjectMember
php artisan make:model PcbProjectVersion
php artisan make:model PcbProjectActivityLog
```

**Implement Relationships:**
- PcbProject belongsTo User (owner)
- PcbProject belongsTo Organization
- PcbProject belongsTo Marketplace
- PcbProject hasMany PcbFile
- PcbProject hasMany PcbDesignRequest
- PcbProject hasMany PcbQuote
- PcbProject hasMany PcbProjectMember

**Week 5 Day 3-5: Project Service**
```bash
php artisan make:service PcbProjectService
```

**Implement:**
- Create project with validation
- Update project details
- Add/remove project members
- Change project status
- Track activity log
- Generate project code (auto-increment)
- Soft delete with cascade protection

**Week 6 Day 1-3: API Controllers**
```bash
php artisan make:controller Api/Pcb/ProjectController --api
php artisan make:controller Api/Pcb/ProjectMemberController
php artisan make:controller Api/Pcb/ProjectFileController
php artisan make:controller Api/Pcb/ProjectActivityController
```

**Endpoints:**
```
GET    /api/pcb/projects          - List user's projects
POST   /api/pcb/projects          - Create project
GET    /api/pcb/projects/{id}     - Get project details
PUT    /api/pcb/projects/{id}     - Update project
DELETE /api/pcb/projects/{id}     - Soft delete project

GET    /api/pcb/projects/{id}/files        - List project files
POST   /api/pcb/projects/{id}/files        - Upload file
DELETE /api/pcb/projects/{id}/files/{file} - Delete file

GET    /api/pcb/projects/{id}/members      - List members
POST   /api/pcb/projects/{id}/members      - Add member
DELETE /api/pcb/projects/{id}/members/{user} - Remove member

GET    /api/pcb/projects/{id}/activity     - Activity log
```

**Week 6 Day 4-5: Authorization Policies**
```bash
php artisan make:policy PcbProjectPolicy --model=PcbProject
php artisan make:policy PcbFilePolicy --model=PcbFile
```

**Implement:**
- viewAny: User's own projects + org projects
- view: Owner, org member, or explicit share
- create: Authenticated users
- update: Owner or admin
- delete: Owner only
- file upload: Members only

**Testing:**
- [ ] Create project via API
- [ ] Add members to project
- [ ] Upload files to project
- [ ] Verify activity log entries
- [ ] Test authorization policies
- [ ] Confirm org isolation

**Deliverables:**
- ✅ Full CRUD for PCB projects
- ✅ File management within projects
- ✅ Member management
- ✅ Activity tracking
- ✅ Authorization enforced

---

### Week 7-8: PCB Project Workspace (Frontend)

#### Tasks

**Week 7 Day 1-2: Project List Page**
- Create Vue/Nuxt component for project list
- Implement search and filtering
- Add sort by date/status/name
- Show project cards with key info
- "Create New Project" button

**Week 7 Day 3-5: Project Detail Shell**
- Create project detail layout
- Implement tab navigation:
  - Overview
  - Requirements
  - Design
  - Files
  - Gerber Viewer (placeholder)
  - BOM
  - CPL
  - Component Matching (placeholder)
  - DFM (placeholder)
  - Quotes
  - Suppliers (placeholder)
  - Messages (placeholder)
  - Orders (placeholder)
  - Production (placeholder)
  - Quality (placeholder)
  - History

**Week 8 Day 1-3: Tab Implementations**
- **Overview Tab:**
  - Project summary card
  - Status badge
  - Key dates
  - Team members
  - Recent activity feed
  
- **Files Tab:**
  - File list with type icons
  - Upload button (reuse Gerber upload)
  - Download with signed URLs
  - File version history
  - Access log viewer (admin)
  
- **Requirements Tab:**
  - Form for project requirements
  - Application type selector
  - Target quantity/budget
  - Timeline fields
  - Special requirements text

**Week 8 Day 4-5: State Management**
- Implement Vuex/Pinia store for PCB state
- Cache project data
- Handle optimistic updates
- Error handling and notifications
- Loading states

**Testing:**
- [ ] Navigate project list
- [ ] Create new project
- [ ] View project details
- [ ] Switch between tabs
- [ ] Upload/download files
- [ ] Test responsive design

**Deliverables:**
- ✅ Project list page
- ✅ Project detail with 15 tabs
- ✅ File management UI
- ✅ Requirements form
- ✅ Activity feed
- ✅ Mobile responsive

---

### Week 9: Quote Configurator Shell & Manual Workflow

#### Tasks

**Day 1-2: Quote Data Model**
```bash
php artisan make:migration create_pcb_quotes_table
php artisan make:migration create_pcb_quote_configurations_table
php artisan make:migration create_pcb_manual_quotes_table
```

**Tables:**
- `pcb_quotes`: id, project_id, status, total_price, currency, valid_until
- `pcb_quote_configurations`: JSON storage of all config choices
- `pcb_manual_quotes`: engineering review notes, custom pricing

**Day 3-4: Quote Configurator UI**
- Create multi-step wizard component
- Implement sections:
  1. Upload Files (completed)
  2. Board Type (single/double/multi)
  3. Dimensions (L × W with units)
  4. Layers (2-20+)
  5. Material (FR-4, aluminum, flex)
  6. Thickness (0.4mm-3.2mm)
  7. Copper (1oz, 2oz, etc.)
  8. Solder Mask Color
  9. Silkscreen Color
  10. Surface Finish (HASL, ENIG, etc.)
  11. Via Options
  12. Impedance Control
  13. Special Processes
  14. Panelization
  15. Testing
  16. Quantity
  17. Lead Time
  18. Assembly Options
  19. BOM/CPL Upload
  20. Stencil
  21. Shipping
  22. Summary

**Day 5: Manual Quote Workflow**
- Create "Request Engineering Quote" button
- Submit configuration for review
- Notify engineering team
- Engineer reviews and enters custom price
- Customer notified when quote ready
- Quote approval/rejection flow

**API Endpoints:**
```
POST /api/pcb/quotes/request        - Submit quote request
GET  /api/pcb/quotes/{id}           - Get quote details
POST /api/pcb/quotes/{id}/approve   - Approve quote
POST /api/pcb/quotes/{id}/reject    - Reject quote
```

**Testing:**
- [ ] Complete quote configuration
- [ ] Submit for manual quote
- [ ] Engineer receives notification
- [ ] Engineer enters custom price
- [ ] Customer sees quote
- [ ] Approve/reject flow works

**Deliverables:**
- ✅ Quote configurator UI (all 22 sections)
- ✅ Configuration saved to database
- ✅ Manual quote request workflow
- ✅ Engineering review interface
- ✅ Customer quote notification

---

### Week 10: Admin Dashboard, Public Pages & SEO

#### Tasks

**Day 1-2: Admin PCB Dashboard**
- Create `/admin/pcb` route
- Dashboard widgets:
  - Active projects count
  - Pending design requests
  - Gerber uploads today
  - Unmatched BOM lines
  - DFM warnings pending
  - Quote requests awaiting review
  - Orders this month
  - Revenue chart
- Quick action buttons
- Recent activity feed

**Day 3-4: Public Landing Pages**
- **Homepage (`/`):**
  - Hero: "Build, Source and Manufacture Your Electronics"
  - CTAs: Upload Gerber, Upload BOM, Start PCB Design, Ask AI
  - Service cards: Fabrication, Assembly, Design, Sourcing, Stencil, DFM, Testing, Custom
  - Why NeoGiga section
  - Trust indicators
  
- **Service Pages:**
  - `/pcb-fabrication` - Capabilities, materials, finishes
  - `/pcb-assembly` - SMT, THT, turnkey options
  - `/pcb-design` - Design services intake
  - `/component-sourcing` - Global sourcing network
  - `/smt-stencil` - Stencil ordering
  - `/dfm-review` - DFM explanation
  - `/capabilities` - Technical capabilities matrix
  - `/pricing` - Pricing guidance (manual quote CTA)
  - `/resources` - LMS links, guides
  - `/support` - Contact, FAQ

**Day 5: SEO Implementation**
- Add meta titles/descriptions to all public pages
- Generate canonical URLs
- Add hreflang tags for marketplaces
- Implement schema.org markup:
  - Service schema for PCB services
  - BreadcrumbList schema
  - Organization schema
  - WebSite SearchAction
- Create XML sitemap (public pages only)
- Add robots.txt with noindex for private routes
- Implement OpenGraph tags
- Add Twitter Card metadata

**Private Page Protection:**
```php
// In app/Http/Middleware/NoIndexPrivatePages.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    if (auth()->check() && strpos($request->path(), 'projects') !== false) {
        $response->header('X-Robots-Tag', 'noindex, nofollow');
    }
    
    return $response;
}
```

**Testing:**
- [ ] Admin dashboard loads with data
- [ ] All public pages render correctly
- [ ] SEO metadata present
- [ ] Sitemap generated
- [ ] Robots.txt correct
- [ ] Private pages have noindex
- [ ] Schema markup validates

**Deliverables:**
- ✅ Admin PCB dashboard
- ✅ Homepage live
- ✅ 10 service landing pages
- ✅ Complete SEO metadata
- ✅ Sitemap and robots.txt
- ✅ Private pages protected from indexing

---

### Stage 1 Completion Checklist

#### Infrastructure
- [ ] pcb.neogiga.com resolves with SSL
- [ ] HTTPS redirect working
- [ ] Cross-subdomain sessions functional
- [ ] CORS configured correctly

#### Authentication
- [ ] PCB roles seeded
- [ ] PCB permissions seeded
- [ ] Role assignment tested
- [ ] Permission checks working

#### Database
- [ ] All migrations created and run
- [ ] Foreign keys validated
- [ ] Indexes verified
- [ ] Rollback tested

#### File Security
- [ ] Private disk configured
- [ ] UUID filenames enforced
- [ ] Authorization middleware active
- [ ] Signed URLs expiring
- [ ] Access logs recording
- [ ] ZIP bomb protection working

#### Project Workspace
- [ ] Project CRUD complete
- [ ] File management working
- [ ] Member management functional
- [ ] Activity logging active
- [ ] Frontend UI responsive

#### Gerber Upload
- [ ] Upload endpoint accepting files
- [ ] ZIP extraction safe
- [ ] Layer detection basic working
- [ ] Malware scan queued

#### Quote System
- [ ] Configurator UI complete (22 sections)
- [ ] Configuration saved
- [ ] Manual quote workflow operational
- [ ] Engineering review interface ready

#### Admin & Public
- [ ] Admin dashboard showing data
- [ ] Homepage live
- [ ] Service pages published
- [ ] SEO metadata complete
- [ ] Sitemap generated
- [ ] Private pages noindex

#### Testing
- [ ] Unit tests written (80% coverage)
- [ ] Feature tests passing
- [ ] Security tests passed
- [ ] Performance baseline established
- [ ] Cross-browser tested
- [ ] Mobile responsive verified

#### Documentation
- [ ] API documentation updated
- [ ] User guide drafted
- [ ] Admin guide created
- [ ] Deployment runbook written

---

## Stage 2: Advanced Features (Weeks 11-24)

### Objective
Add Gerber viewer, automated analysis, manufacturer capabilities, pricing engine, BOM/CPL integration, and component matching.

### Key Deliverables
- 🟠 Self-hosted Gerber viewer integration
- 🟠 Gerber analysis engine
- 🟠 Manufacturer capability database
- 🟠 PCB pricing calculator
- 🟠 CPL import and validation
- 🟠 Component matching with alternatives
- 🟠 Engineering review workflow
- 🟠 Localization strings

### Duration: 14 weeks

*(Detailed task breakdown available upon Stage 1 completion)*

---

## Stage 3: Manufacturing Integration (Weeks 25-44)

### Objective
Complete PCBA pricing, DFM automation, supplier RFQ, quote comparison, order conversion, manufacturing tracking, and quality system.

### Key Deliverables
- 🟡 PCBA pricing engine
- 🟡 Automated DFM checks
- 🟡 Supplier invitation system
- 🟡 Quote comparison UI
- 🟡 Cart/order integration
- 🟡 Split purchase orders
- 🟡 Production stage tracking
- 🟡 Quality complaint system

### Duration: 20 weeks

*(Detailed task breakdown available upon Stage 2 completion)*

---

## Stage 4: Polish & Scale (Weeks 45-56)

### Objective
Supplier portal, advanced analytics, AI assistant, performance optimization, comprehensive testing, production rollout.

### Key Deliverables
- 🟢 Supplier portal
- 🟢 Advanced analytics dashboards
- 🟢 AI PCB assistant
- 🟢 Performance optimization
- 🟢 Comprehensive test suite
- 🟢 Production deployment

### Duration: 12 weeks

*(Detailed task breakdown available upon Stage 3 completion)*

---

## Risk Management

### High-Risk Items

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Private file exposure | Low | Critical | Multiple security layers, penetration testing |
| Cross-org data leak | Low | Critical | Strict authorization, extensive testing |
| Gerber parsing errors | Medium | High | Manual review fallback, confidence scoring |
| Incorrect pricing | Medium | High | Engineering approval workflow, price floors |
| Component mismatch | Medium | Medium | Confidence scoring, customer approval required |
| DFM false negatives | Medium | High | Engineering review mandatory for all jobs |

### Contingency Plans

1. **If file security compromised:** Immediate takedown, forensic analysis, customer notification
2. **If pricing incorrect:** Manual review queue, price adjustment process, customer communication
3. **If Gerber parser fails:** Fallback to manual engineering review
4. **If performance issues:** Queue prioritization, caching strategy, horizontal scaling

---

## Success Metrics

### Stage 1 KPIs
- 100% security test pass rate
- < 2 second page load time
- 99.9% uptime during testing
- Zero data leakage incidents
- All critical path items complete

### Stage 2 KPIs
- 90% Gerber layer detection accuracy
- < 5 minute quote configuration time
- 95% BOM match rate for common parts
- < 1 second viewer load time

### Stage 3 KPIs
- 80% DFM issue detection rate
- < 24 hour engineer response time
- 95% order conversion success
- Real-time production updates

### Stage 4 KPIs
- 90% customer satisfaction score
- < 1% quality complaint rate
- 99.95% platform uptime
- Positive unit economics

---

## Resource Requirements

### Development Team
- 1 Principal Architect (part-time oversight)
- 1 Senior Backend Engineer (full-time)
- 1 Senior Frontend Engineer (full-time)
- 1 DevOps Engineer (part-time)
- 1 QA Engineer (part-time)
- 1 Security Engineer (consulting)

### Infrastructure
- Development environment
- Staging environment (mirror of production)
- Production environment
- S3-compatible object storage
- Redis cluster for queues
- PostgreSQL read replicas (future)

### Third-Party Services
- SSL certificate (Let's Encrypt - free)
- Gerber viewer library (open-source)
- Antivirus scanning (ClamAV - open-source)
- Optional: Cloud AV service for redundancy

---

## Next Actions

### Immediate (This Week)
1. Review and approve this implementation plan
2. Provision pcb.neogiga.com DNS and SSL
3. Set up development environment
4. Begin Week 1 tasks

### Pre-Stage 1 Prerequisites
- [ ] Git branch created: `feature/pcb-platform-stage-1`
- [ ] Development database provisioned
- [ ] CI/CD pipeline configured
- [ ] Monitoring tools ready
- [ ] Team access granted

---

## Appendix A: Database Migration Order

```
1. create_pcb_projects_table
2. create_pcb_project_members_table
3. create_pcb_project_versions_table
4. create_pcb_project_tags_table
5. create_pcb_project_comments_table
6. create_pcb_project_attachments_table
7. create_pcb_project_activity_logs_table
8. create_pcb_files_table
9. create_pcb_file_versions_table
10. create_pcb_file_shares_table
11. create_pcb_file_access_logs_table
12. create_pcb_file_scan_results_table
13. create_pcb_file_retention_policies_table
14. add_cpl_fields_to_bom_tables
15. create_pcb_quotes_table
16. create_pcb_quote_configurations_table
17. create_pcb_manual_quotes_table
```

---

## Appendix B: API Route Map

```php
// Public routes
Route::prefix('pcb')->group(function () {
    Route::get('/', [PcbHomeController::class, 'index']);
    Route::get('/pcb-fabrication', [PcbServiceController::class, 'fabrication']);
    Route::get('/pcb-assembly', [PcbServiceController::class, 'assembly']);
    Route::get('/pcb-design', [PcbServiceController::class, 'design']);
    // ... other service pages
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->prefix('pcb')->group(function () {
    Route::apiResource('projects', PcbProjectController::class);
    Route::apiResource('projects.files', PcbProjectFileController::class);
    Route::apiResource('projects.members', PcbProjectMemberController::class);
    Route::post('quotes/request', [PcbQuoteController::class, 'request']);
    Route::get('quotes/{id}', [PcbQuoteController::class, 'show']);
    // ... more routes
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin/pcb')->group(function () {
    Route::get('/', [AdminPcbDashboardController::class, 'index']);
    Route::get('projects', [AdminPcbProjectController::class, 'index']);
    // ... admin routes
});
```

---

## Document Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Project Sponsor | | | |
| Technical Lead | | | |
| Security Lead | | | |
| QA Lead | | | |

---

**Next Review Date:** After Stage 1 completion  
**Document Owner:** Principal Platform Architect
