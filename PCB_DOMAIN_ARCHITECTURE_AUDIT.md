# PCB Domain Architecture Audit

**Document:** PCB_DOMAIN_ARCHITECTURE_AUDIT.md  
**Date:** 2026-07-11  
**Author:** Principal Platform Architect, NeoGiga  
**Version:** 1.0  

---

## Executive Summary

This document defines the domain architecture for `pcb.neogiga.com` as an integrated subdomain of the NeoGiga platform. The architecture ensures shared authentication, unified database, and seamless user experience across all NeoGiga properties while maintaining security isolation for sensitive PCB design files.

---

## 1. Domain Strategy

### 1.1 Domain Hierarchy

```
neogiga.com (main platform)
├── www.neogiga.com (storefront)
├── admin.neogiga.com (admin panel)
├── pcb.neogiga.com (PCB platform) ← NEW
├── seller.neogiga.com (seller portal)
└── supplier.neogiga.com (supplier/manufacturer portal)
```

### 1.2 URL Routing Strategy

**Option A: Subdomain Approach (Recommended)**

| Domain | Purpose | Shared Auth | Notes |
|--------|---------|-------------|-------|
| `neogiga.com/en` | Global English storefront | ✅ Yes | Main commerce site |
| `neogiga.com/np` | Nepal marketplace | ✅ Yes | Localized |
| `neogiga.com/in` | India marketplace | ✅ Yes | Localized |
| `neogiga.com/bd` | Bangladesh marketplace | ✅ Yes | Localized |
| `neogiga.com/mm` | Myanmar marketplace | ✅ Yes | Localized |
| `neogiga.com/au` | Australia marketplace | ✅ Yes | Localized |
| `pcb.neogiga.com` | PCB platform (global) | ✅ Yes | Dedicated PCB workspace |
| `pcb.neogiga.com/np` | PCB Nepal | ✅ Yes | Localized PCB pricing |
| `pcb.neogiga.com/in` | PCB India | ✅ Yes | Localized PCB pricing |

**Option B: Subdirectory Approach (Alternative)**

| URL Path | Purpose |
|----------|---------|
| `neogiga.com/en/pcb` | PCB platform redirect |
| `neogiga.com/en/pcb/projects` | PCB projects |
| `neogiga.com/en/pcb/quote` | PCB quote configurator |

**Decision:** Use **Option A (Subdomain)** for pcb.neogiga.com because:
- Clear separation of PCB workflow from general commerce
- Dedicated branding for PCB services
- Easier to scale independently
- Better performance isolation
- Cleaner URL structure for complex PCB workflows

### 1.3 Canonical URL Strategy

**Public Pages:**
```
pcb.neogiga.com/pcb-fabrication → canonical to itself
pcb.neogiga.com/pcb-assembly → canonical to itself
pcb.neogiga.com/pcb-design → canonical to itself
```

**Localized Public Pages:**
```
pcb.neogiga.com/np/pcb-fabrication → hreflang to other locales
pcb.neogiga.com/in/pcb-fabrication → hreflang to other locales
```

**Private Pages (noindex):**
```
pcb.neogiga.com/projects/{id} → noindex, nofollow
pcb.neogiga.com/projects/{id}/files → noindex, nofollow
pcb.neogiga.com/projects/{id}/quote → noindex, nofollow
```

**Cross-Domain Canonicals:**
```
neogiga.com/en/pcb → 301 redirect to pcb.neogiga.com
neogiga.com/en/pcb/quote → 301 redirect to pcb.neogiga.com/quote
```

---

## 2. Application Architecture

### 2.1 High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         DNS Layer                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │ neogiga.com │  │ pcb.neogiga │  │ admin.neogiga.com       │ │
│  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘ │
└─────────┼────────────────┼─────────────────────┼────────────────┘
          │                │                     │
┌─────────▼────────────────▼─────────────────────▼────────────────┐
│                    Load Balancer / Reverse Proxy                 │
│                         (Nginx/Traefik)                          │
└─────────┬────────────────┬─────────────────────┬────────────────┘
          │                │                     │
┌─────────▼────────────────▼─────────────────────▼────────────────┐
│                   Laravel Application (Shared)                   │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ Frontend Layer                                             │  │
│  │ ├── neogiga.com (Nuxt/Vue Storefront)                      │  │
│  │ ├── pcb.neogiga.com (PCB-specific frontend)                │  │
│  │ └── admin.neogiga.com (Admin dashboard)                    │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ API Layer (Unified)                                        │  │
│  │ ├── /api/v1/auth/*                                         │  │
│  │ ├── /api/v1/products/*                                     │  │
│  │ ├── /api/v1/pcb/* (NEW)                                    │  │
│  │ ├── /api/v1/orders/*                                       │  │
│  │ └── ...                                                    │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ Business Logic Layer                                       │  │
│  │ ├── AuthService                                            │  │
│  │ ├── ProductService                                         │  │
│  │ ├── PcbProjectService (NEW)                                │  │
│  │ ├── PcbFileService (NEW)                                   │  │
│  │ ├── PcbQuoteService (NEW)                                  │  │
│  │ ├── OrderService                                           │  │
│  │ └── ...                                                    │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────┬────────────────┬─────────────────────┬────────────────┘
          │                │                     │
┌─────────▼────────────────▼─────────────────────▼────────────────┐
│                      PostgreSQL Database                         │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ Shared Tables                                              │  │
│  │ ├── users, roles, permissions                              │  │
│  │ ├── products, categories, brands                           │  │
│  │ ├── orders, order_items                                    │  │
│  │ ├── carts, cart_items                                      │  │
│  │ ├── payments                                               │  │
│  │ └── ...                                                    │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ PCB-Specific Tables (NEW)                                  │  │
│  │ ├── pcb_projects                                           │  │
│  │ ├── pcb_files                                              │  │
│  │ ├── pcb_quotes                                             │  │
│  │ ├── pcb_cpl_imports                                        │  │
│  │ ├── pcb_dfm_runs                                           │  │
│  │ └── ...                                                    │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
          │
┌─────────▼───────────────────────────────────────────────────────┐
│                     External Services                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │ File Storage│  │ Queue Workers│  │ Cache (Redis)          │ │
│  │ (Private)   │  │ (PCB queues) │  │                        │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Request Flow

#### Public User Visiting PCB Homepage

```
1. User → pcb.neogiga.com
2. DNS → Load Balancer
3. Load Balancer → Laravel App
4. Laravel detects subdomain (pcb.neogiga.com)
5. Middleware sets marketplace context
6. Return PCB homepage view
7. SEO metadata rendered server-side
```

#### Authenticated User Accessing PCB Project

```
1. User → pcb.neogiga.com/projects/uuid
2. Auth middleware validates token/session
3. Authorization check: user can access project?
4. Organization scope check: project belongs to user's org?
5. Load project data + files metadata
6. Return project workspace view
7. Files served via signed URLs only
```

#### Gerber File Upload

```
1. User uploads ZIP via frontend
2. Frontend → POST /api/v1/pcb/projects/{id}/files
3. Auth + Authorization middleware
4. File validation (MIME, size, virus scan queued)
5. Store in private storage (no public URL)
6. Create pcb_files record
7. Queue pcb-file-process job
8. Return file metadata to frontend
9. Background job parses Gerber, extracts layers
10. Update pcb_file_analysis_runs when complete
11. Notify user via queue
```

---

## 3. Virtual Host Configuration

### 3.1 Nginx Server Block for pcb.neogiga.com

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name pcb.neogiga.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pcb.neogiga.com;
    
    root /home/neogiga/laravel/current/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/neogiga.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/neogiga.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:; frame-ancestors 'none';" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Hide server version
    server_tokens off;
    
    # Disable directory listing
    autoindex off;
    
    # CORS for API (adjust origins as needed)
    location /api/ {
        add_header Access-Control-Allow-Origin "https://pcb.neogiga.com" always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" always;
        add_header Access-Control-Allow-Credentials "true" always;
        
        if ($request_method = OPTIONS) {
            add_header Access-Control-Max-Age 1728000;
            add_header Content-Type 'text/plain charset=UTF-8';
            add_header Content-Length 0;
            return 204;
        }
        
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Private file storage (no direct access)
    location /storage/pcb-private/ {
        internal;  # Only accessible via X-Accel-Redirect
        alias /home/neogiga/laravel/current/storage/pcb-private/;
    }
    
    # Public assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(vendor|node_modules|bootstrap|config|database|resources|routes|storage|tests) {
        deny all;
    }
    
    # Main application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Health endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
```

### 3.2 SSL Certificate Strategy

**Option 1: Wildcard Certificate (Recommended)**
```
*.neogiga.com covers:
- pcb.neogiga.com
- admin.neogiga.com
- seller.neogiga.com
- etc.
```

**Option 2: Multi-Domain Certificate**
```
Separate certificates for each subdomain
More complex management
Better isolation if one cert compromised
```

**Implementation:**
```bash
# Let's Encrypt wildcard certificate
certbot certonly --dns-provider dns_<your_provider> \
  -d neogiga.com -d *.neogiga.com
```

---

## 4. Authentication Flow

### 4.1 Single Sign-On Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    Authentication Flow                        │
└──────────────────────────────────────────────────────────────┘

1. User logs in at neogiga.com/login
   ↓
2. Laravel creates session + token
   ↓
3. Session cookie set with domain=.neogiga.com
   ↓
4. User navigates to pcb.neogiga.com
   ↓
5. Browser sends cookie automatically (same parent domain)
   ↓
6. pcb.neogiga.com validates session/token
   ↓
7. User authenticated without re-login
```

### 4.2 Cookie Configuration

```php
// config/session.php
[
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env(
        'SESSION_COOKIE',
        'neogiga_session'
    ),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', '.neogiga.com'),  // Critical!
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',  // or 'strict' for higher security
]
```

### 4.3 Token-Based API Auth

```php
// For API calls from frontend to backend
Headers:
Authorization: Bearer {token}
X-Marketplace-Code: np (optional, for localization)

Token contains:
- user_id
- organization_id (if applicable)
- roles
- permissions
- marketplace context
- expiry
```

### 4.4 Permission Checks

```php
// Example middleware check
class PcbProjectAccess
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $project = PcbProject::findOrFail($request->route('project'));
        
        // Check organization membership
        if ($user->organization_id !== $project->organization_id) {
            abort(403, 'Unauthorized project access');
        }
        
        // Check specific permission
        if (!$user->hasPermission('pcb.project.view')) {
            abort(403, 'Missing PCB project view permission');
        }
        
        return $next($request);
    }
}
```

---

## 5. Database Isolation Strategy

### 5.1 Row-Level Security

All PCB tables must include organization scoping:

```php
// Every PCB model
class PcbProject extends Model
{
    // Scope queries to user's organization
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
    
    // Check access
    public function canAccess(User $user): bool
    {
        return $user->organization_id === $this->organization_id
            || $user->hasRole('super_admin')
            || $this->members()->where('user_id', $user->id)->exists();
    }
}
```

### 5.2 Supplier Access Control

```php
// Temporary supplier access to project files
class PcbFileShare extends Model
{
    protected $fillable = [
        'pcb_file_id',
        'supplier_id',
        'access_token',
        'expires_at',
        'nda_accepted',
        'nda_accepted_at',
    ];
    
    public function isValid(): bool
    {
        return $this->expires_at->isFuture()
            && ($this->nda_required ? $this->nda_accepted : true);
    }
    
    public function revoke(): void
    {
        $this->update(['expires_at' => now()]);
    }
}
```

---

## 6. CORS Configuration

### 6.1 CORS Settings for Subdomain

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_origins' => [
        'https://neogiga.com',
        'https://www.neogiga.com',
        'https://pcb.neogiga.com',
        'https://admin.neogiga.com',
        'https://seller.neogiga.com',
    ],
    
    'allowed_origins_patterns' => [
        '#^https://.*\.neogiga\.com$#',  // Allow all neogiga.com subdomains
    ],
    
    'allowed_methods' => ['*'],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,  // Critical for cookies
];
```

---

## 7. CSRF Protection

### 7.1 CSRF Configuration

```php
// config/session.php
[
    'csrf_cookie_name' => 'XSRF-TOKEN',
]

// config/cors.php (supports_credentials must be true)

// Frontend must send:
// X-XSRF-TOKEN header with state-changing requests
```

### 7.2 CSRF Exemptions

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/v1/pcb/files/upload',  // File uploads use token auth
    'api/v1/pcb/gerber/webhook', // External callbacks
];
```

---

## 8. Routing Structure

### 8.1 Public Routes (pcb.neogiga.com)

```php
// routes/web.php (PCB-specific)

Route::domain('pcb.neogiga.com')->group(function () {
    // Public pages
    Route::get('/', [PcbHomeController::class, 'index']);
    Route::get('/pcb-fabrication', [PcbFabricationController::class, 'index']);
    Route::get('/pcb-assembly', [PcbAssemblyController::class, 'index']);
    Route::get('/pcb-design', [PcbDesignController::class, 'index']);
    Route::get('/component-sourcing', [ComponentSourcingController::class, 'index']);
    Route::get('/smt-stencil', [SmtStencilController::class, 'index']);
    Route::get('/dfm-review', [DfmReviewController::class, 'index']);
    Route::get('/capabilities', [CapabilitiesController::class, 'index']);
    Route::get('/materials', [MaterialsController::class, 'index']);
    Route::get('/resources', [ResourcesController::class, 'index']);
    Route::get('/pricing', [PricingController::class, 'index']);
    Route::get('/support', [SupportController::class, 'index']);
    
    // Localized public pages
    Route::get('/{marketplace}', [PcbHomeController::class, 'localizedIndex'])
        ->where('marketplace', '(np|in|bd|mm|au)');
    
    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        Route::prefix('projects')->group(function () {
            Route::get('/', [PcbProjectController::class, 'index']);
            Route::post('/', [PcbProjectController::class, 'store']);
            Route::get('/{project}', [PcbProjectController::class, 'show']);
            Route::patch('/{project}', [PcbProjectController::class, 'update']);
            Route::delete('/{project}', [PcbProjectController::class, 'destroy']);
            
            // Project tabs
            Route::get('/{project}/requirements', [PcbRequirementsController::class, 'show']);
            Route::get('/{project}/design', [PcbDesignController::class, 'show']);
            Route::get('/{project}/files', [PcbFilesController::class, 'show']);
            Route::get('/{project}/gerber', [PcbGerberController::class, 'show']);
            Route::get('/{project}/bom', [PcbBomController::class, 'show']);
            Route::get('/{project}/cpl', [PcbCplController::class, 'show']);
            Route::get('/{project}/dfm', [PcbDfmController::class, 'show']);
            Route::get('/{project}/quote', [PcbQuoteController::class, 'show']);
            Route::get('/{project}/suppliers', [PcbSuppliersController::class, 'show']);
            Route::get('/{project}/messages', [PcbMessagesController::class, 'show']);
            Route::get('/{project}/orders', [PcbOrdersController::class, 'show']);
            Route::get('/{project}/production', [PcbProductionController::class, 'show']);
            Route::get('/{project}/quality', [PcbQualityController::class, 'show']);
            Route::get('/{project}/history', [PcbHistoryController::class, 'show']);
            
            // File operations
            Route::post('/{project}/files', [PcbFilesController::class, 'store']);
            Route::post('/{project}/gerber/upload', [PcbGerberController::class, 'upload']);
            Route::post('/{project}/bom/upload', [PcbBomController::class, 'upload']);
            Route::post('/{project}/cpl/upload', [PcbCplController::class, 'upload']);
        });
        
        // Quote operations
        Route::post('/quote/calculate', [PcbQuoteController::class, 'calculate']);
        Route::post('/quote/submit-rfq', [PcbQuoteController::class, 'submitRfq']);
        
        // Cart integration
        Route::post('/quote/add-to-cart', [PcbQuoteController::class, 'addToCart']);
    });
});
```

### 8.2 API Routes

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // PCB API routes
    Route::prefix('pcb')->group(function () {
        // Public endpoints
        Route::get('/capabilities', [PcbCapabilitiesController::class, 'index']);
        Route::get('/materials', [PcbMaterialsController::class, 'index']);
        Route::get('/pricing-guide', [PcbPricingController::class, 'guide']);
        
        // Protected endpoints
        Route::middleware('api.token')->group(function () {
            // Projects
            Route::apiResource('projects', PcbProjectController::class);
            
            // Files
            Route::post('projects/{project}/files', [PcbFileController::class, 'store']);
            Route::get('files/{file}/download', [PcbFileController::class, 'download']);
            Route::delete('files/{file}', [PcbFileController::class, 'destroy']);
            
            // Gerber
            Route::post('projects/{project}/gerber', [PcbGerberController::class, 'upload']);
            Route::get('projects/{project}/gerber/analysis', [PcbGerberController::class, 'analysis']);
            
            // BOM
            Route::post('projects/{project}/bom', [PcbBomController::class, 'upload']);
            Route::get('projects/{project}/bom/matches', [PcbBomController::class, 'matches']);
            Route::post('projects/{project}/bom/approve-substitution', [PcbBomController::class, 'approveSubstitution']);
            
            // CPL
            Route::post('projects/{project}/cpl', [PcbCplController::class, 'upload']);
            Route::get('projects/{project}/cpl/validation', [PcbCplController::class, 'validation']);
            
            // DFM
            Route::get('projects/{project}/dfm', [PcbDfmController::class, 'run']);
            Route::get('projects/{project}/dfm/issues', [PcbDfmController::class, 'issues']);
            Route::post('projects/{project}/dfm/issues/{issue}/waive', [PcbDfmController::class, 'waive']);
            
            // Quotes
            Route::post('quote/calculate', [PcbQuoteController::class, 'calculate']);
            Route::post('quote/request-engineering', [PcbQuoteController::class, 'requestEngineering']);
            Route::get('quote/{quote}', [PcbQuoteController::class, 'show']);
            Route::post('quote/{quote}/approve', [PcbQuoteController::class, 'approve']);
            Route::post('quote/{quote}/add-to-cart', [PcbQuoteController::class, 'addToCart']);
            
            // Supplier RFQ (Phase 3+)
            // Route::post('quote/{quote}/send-rfq', [PcbRfqController::class, 'send']);
            
            // Production tracking (Phase 3+)
            // Route::get('orders/{order}/production', [PcbProductionController::class, 'status']);
        });
    });
});
```

---

## 9. Middleware Stack

### 9.1 Custom Middleware

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
    ],
    
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    
    'pcb.auth' => [
        \App\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\Pcb\SetPcbContext::class,
    ],
    
    'pcb.project.access' => [
        \App\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\Pcb\ProjectAccess::class,
    ],
    
    'pcb.file.secure' => [
        \App\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\Pcb\FileAuthorization::class,
    ],
];

protected $routeMiddleware = [
    // ... existing
    'pcb.marketplace' => \App\Http\Middleware\Pcb\SetMarketplaceContext::class,
    'pcb.organization' => \App\Http\Middleware\Pcb\ScopeToOrganization::class,
];
```

---

## 10. Health Monitoring

### 10.1 Health Endpoints

```php
// routes/health.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
            'cache' => Cache::put('health_check', 1, 10) ? 'ok' : 'fail',
            'queue' => Queue::size() >= 0 ? 'ok' : 'fail',
            'storage' => is_writable(storage_path()) ? 'ok' : 'fail',
        ],
    ]);
})->name('health');

Route::get('/ready', function () {
    // Check if app is ready to serve traffic
    $checks = [
        'migrations' => ! Migration::pending(),
        'config_cached' => app()->configurationIsCached(),
        'routes_cached' => app()->routesAreCached(),
    ];
    
    $ready = collect($checks)->every(fn($passed) => $passed);
    
    return response()->json([
        'ready' => $ready,
        'checks' => $checks,
    ], $ready ? 200 : 503);
})->name('ready');
```

---

## 11. Deployment Topology

### 11.1 Server Roles

```
┌─────────────────────────────────────────────────────────────┐
│ Production Environment                                       │
├─────────────────────────────────────────────────────────────┤
│ Web Servers (2+)                                             │
│ - Nginx + PHP-FPM                                            │
│ - Laravel application                                        │
│ - Shared via rsync (without --delete)                        │
├─────────────────────────────────────────────────────────────┤
│ Database Server                                              │
│ - PostgreSQL 14+                                             │
│ - Read replicas for reporting                                │
├─────────────────────────────────────────────────────────────┤
│ Cache Server                                                 │
│ - Redis                                                      │
│ - Sessions, cache, queues                                    │
├─────────────────────────────────────────────────────────────┤
│ Queue Workers (dedicated)                                    │
│ - pcb-* queues                                               │
│ - Separate workers for PCB processing                        │
├─────────────────────────────────────────────────────────────┤
│ File Storage                                                 │
│ - Local: public assets                                       │
│ - Private disk: PCB files (encrypted)                        │
│ - Optional: S3 for scale                                     │
└─────────────────────────────────────────────────────────────┘
```

### 11.2 Zero-Downtime Deployment

```bash
#!/bin/bash
# deploy-pcb.sh

set -e

RELEASE_DIR="/home/neogiga/laravel/releases/$(date +%Y%m%d%H%M%S)"
CURRENT_LINK="/home/neogiga/laravel/current"

echo "Creating new release: $RELEASE_DIR"
mkdir -p $RELEASE_DIR

# Clone code
git clone /path/to/repo $RELEASE_DIR
cd $RELEASE_DIR

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Copy .env from current
cp $CURRENT_LINK/.env .env

# Run migrations (safe, additive only)
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache

# Atomic switch
ln -sfn $RELEASE_DIR $CURRENT_LINK

# Restart workers
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# Verify health
curl -f https://pcb.neogiga.com/health || exit 1

echo "Deployment successful"
```

---

## 12. Rollback Plan

### 12.1 Rollback Procedure

```bash
#!/bin/bash
# rollback.sh

PREVIOUS_RELEASE=$(ls -t /home/neogiga/laravel/releases | head -2 | tail -1)
CURRENT_LINK="/home/neogiga/laravel/current"

echo "Rolling back to: $PREVIOUS_RELEASE"

# Atomic switch back
ln -sfn "/home/neogiga/laravel/releases/$PREVIOUS_RELEASE" $CURRENT_LINK

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# Verify
curl -f https://pcb.neogiga.com/health || exit 1

echo "Rollback complete"
```

### 12.2 Database Rollback

**Critical:** Only roll back code, not database. All migrations must be:
- Additive (never destructive)
- Reversible (down() method implemented)
- Safe to run multiple times

If migration caused issues:
```bash
php artisan migrate:rollback --step=1
```

---

## 13. Monitoring and Alerting

### 13.1 Key Metrics

| Metric | Threshold | Alert |
|--------|-----------|-------|
| Response time (p95) | < 500ms | > 1000ms |
| Error rate | < 1% | > 5% |
| Queue depth | < 1000 | > 5000 |
| File upload failures | < 1% | > 10% |
| Auth failures | < 0.1% | > 1% |
| Database connections | < 80% max | > 90% |

### 13.2 Logging

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'error_log'],
        'ignore_exceptions' => false,
    ],
    
    'pcb' => [
        'driver' => 'daily',
        'path' => storage_path('logs/pcb.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 30,
    ],
    
    'pcb-security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/pcb-security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
],
```

---

## 14. Security Checklist

### 14.1 Pre-Launch Security Review

- [ ] SSL certificate installed and valid
- [ ] HTTPS redirect working
- [ ] Security headers configured
- [ ] CORS properly restricted
- [ ] CSRF protection enabled
- [ ] Session cookies secure and httpOnly
- [ ] Rate limiting on all write endpoints
- [ ] File upload validation implemented
- [ ] Private file storage configured
- [ ] Signed URL generation working
- [ ] Authorization checks on all PCB resources
- [ ] SQL injection prevention (using Eloquent)
- [ ] XSS prevention (escaping output)
- [ ] Directory listing disabled
- [ ] Sensitive files blocked by Nginx
- [ ] Error messages don't leak stack traces
- [ ] Audit logging enabled
- [ ] Backup procedures tested
- [ ] Rollback procedure tested

---

**End of Domain Architecture Document**
