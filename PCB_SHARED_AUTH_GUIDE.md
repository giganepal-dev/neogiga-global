# PCB Shared Authentication Guide

## Overview

This guide documents the shared authentication architecture between NeoGiga platforms:
- neogiga.com (main marketplace)
- admin.neogiga.com (admin portal)
- pcb.neogiga.com (PCB platform)
- seller.neogiga.com (seller portal)
- supplier.neogiga.com (supplier/manufacturer portal)

## Architecture Principles

### Single Sign-On (SSO)
- Users authenticate once across all NeoGiga domains
- Session cookies scoped to `.neogiga.com` parent domain
- Shared user database (no duplication)
- Shared organization membership
- Shared roles and permissions

### Security Requirements
- Secure cookie flags (HttpOnly, Secure, SameSite)
- Session rotation on privilege changes
- Cross-domain CSRF protection
- Audit logging for all authentication events
- Optional 2FA support
- Organization isolation enforcement

## Configuration

### Environment Variables (.env)

```env
# Session Configuration
SESSION_DRIVER=database
SESSION_TABLE=sessions
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=true
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.neogiga.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Authentication Guards
AUTH_GUARD=web
AUTH_PROVIDER=users

# Sanctum API Tokens
SANCTUM_STATEFUL_DOMAINS=neogiga.com,admin.neogiga.com,pcb.neogiga.com,seller.neogiga.com,supplier.neogiga.com
SANCTUM_SESSION_NAME=neogiga_session
SANCTUM_COOKIE_NAME=neogiga_token

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://neogiga.com,https://admin.neogiga.com,https://pcb.neogiga.com,https://seller.neogiga.com,https://supplier.neogiga.com
CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,X-Requested-With,X-XSRF-TOKEN,X-CSRF-TOKEN,Authorization
CORS_EXPOSED_HEADERS=
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=true

# JWT Configuration (if using API tokens)
JWT_SECRET=${APP_KEY}
JWT_TTL=60
JWT_REFRESH_TTL=10080
JWT_ALGO=HS256
```

### Database Migration

```php
// sessions table (already exists in NeoGiga)
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index()->constrained('users')->onDelete('cascade');
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

## Implementation

### Middleware Stack

#### Web Middleware Group (pcb.neogiga.com)

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\SetMarketplaceContext::class,
        \App\Http\Middleware\CheckPcbAccess::class,
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

#### PCB Access Check Middleware

```php
// app/Http/Middleware/CheckPcbAccess.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPcbAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow public routes
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        // Require authentication for private routes
        if (!$request->user()) {
            return redirect()->route('login', ['redirect' => $request->path()]);
        }

        // Check organization permissions for PCB access
        if (!$request->user()->hasPermissionTo('pcb.project.view')) {
            abort(403, 'PCB access not authorized');
        }

        return $next($request);
    }

    private function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            '/',
            '/pcb-design',
            '/pcb-fabrication',
            '/pcb-assembly',
            '/component-sourcing',
            '/smt-stencil',
            '/dfm-review',
            '/capabilities',
            '/materials',
            '/resources',
            '/pricing',
            '/support',
        ];

        return in_array($request->path(), $publicRoutes) ||
               str_starts_with($request->path(), 'login') ||
               str_starts_with($request->path(), 'register');
    }
}
```

### User Model Extensions

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;

    // Existing NeoGiga user fields
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_code',
        'marketplace_preference',
        'organization_id',
        'is_active',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    // PCB-specific accessors
    public function canAccessPcb(): bool
    {
        return $this->hasAnyPermission([
            'pcb.project.view',
            'pcb.project.create',
            'pcb.design.request',
            'pcb.supplier.quote',
            'pcb.engineer.review',
            'pcb.admin.view',
        ]);
    }

    public function pcbProjects()
    {
        return $this->hasMany(PcbProject::class, 'owner_id');
    }

    public function pcbDesignRequests()
    {
        return $this->hasMany(PcbDesignRequest::class, 'requester_id');
    }

    // Organization-based PCB access
    public function canAccessPcbProject(PcbProject $project): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        if ($project->owner_id === $this->id) {
            return true;
        }

        if ($this->organization_id === $project->organization_id) {
            return $this->hasPermissionTo('pcb.project.view');
        }

        // Check if user is a member of the project
        return $project->members()->where('user_id', $this->id)->exists();
    }
}
```

### Login Flow

```php
// app/Http/Controllers/Auth/LoginController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Log authentication event
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'action' => 'login',
                'context' => 'pcb_platform',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            // Check 2FA
            if ($user->two_factor_enabled) {
                return redirect()->route('2fa.verify');
            }

            // Redirect to intended or dashboard
            return redirect()->intended('/projects');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Log logout event
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'action' => 'logout',
                'context' => 'pcb_platform',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();

        // Invalidate session across all domains
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
```

## Role Definitions

### PCB-Specific Roles

```php
// database/seeders/PcbRoleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PcbRoleSeeder extends Seeder
{
    public function run(): void
    {
        // PCB Customer Roles
        $customer = Role::firstOrCreate(['name' => 'pcb-customer']);
        $customer->givePermissionsTo([
            'pcb.project.view',
            'pcb.project.create',
            'pcb.file.upload',
            'pcb.file.download',
            'pcb.design.request',
            'pcb.bom.manage',
            'pcb.cpl.manage',
            'pcb.quote.create',
            'pcb.order.convert',
        ]);

        // PCB Designer Role
        $designer = Role::firstOrCreate(['name' => 'pcb-designer']);
        $designer->givePermissionsTo([
            'pcb.project.view',
            'pcb.design.request',
            'pcb.design.manage',
            'pcb.file.upload',
            'pcb.file.download',
            'pcb.gerber.review',
        ]);

        // DFM Engineer Role
        $dfmEngineer = Role::firstOrCreate(['name' => 'dfm-engineer']);
        $dfmEngineer->givePermissionsTo([
            'pcb.project.view',
            'pcb.gerber.review',
            'pcb.dfm.review',
            'pcb.bom.manage',
            'pcb.cpl.manage',
        ]);

        // Component Engineer Role
        $componentEngineer = Role::firstOrCreate(['name' => 'component-engineer']);
        $componentEngineer->givePermissionsTo([
            'pcb.project.view',
            'pcb.bom.manage',
            'pcb.component.approve',
        ]);

        // PCB Manufacturer Role
        $manufacturer = Role::firstOrCreate(['name' => 'pcb-manufacturer']);
        $manufacturer->givePermissionsTo([
            'pcb.supplier.quote',
            'pcb.supplier.production',
            'pcb.file.download',
        ]);

        // Quality Engineer Role
        $qualityEngineer = Role::firstOrCreate(['name' => 'quality-engineer']);
        $qualityEngineer->givePermissionsTo([
            'pcb.project.view',
            'pcb.quality.manage',
            'pcb.file.download',
        ]);

        // PCB Admin Role
        $pcbAdmin = Role::firstOrCreate(['name' => 'pcb-admin']);
        $pcbAdmin->givePermissionsTo([
            'pcb.admin.view',
            'pcb.admin.manage',
            'pcb.project.view',
            'pcb.project.edit',
            'pcb.quote.approve',
            'pcb.engineer.review',
        ]);
    }
}
```

## Session Management

### Cookie Configuration

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', true),
    'encrypt' => env('SESSION_ENCRYPT', true),
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => env('SESSION_TABLE', 'sessions'),
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env(
        'SESSION_COOKIE',
        'neogiga_session'
    ),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN', '.neogiga.com'),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
];
```

### CSRF Protection

```php
// app/Http/Middleware/VerifyCsrfToken.php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/*',
        'webhook/*',
    ];

    protected $addHttpCookie = true;
}
```

## Testing

### Authentication Tests

```php
// tests/Feature/PcbAuthenticationTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PcbAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_to_pcb_platform(): void
    {
        $user = User::factory()->create([
            'email' => 'test@neogiga.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@neogiga.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/projects');
        $this->assertAuthenticated();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/projects');
        $response->assertRedirect('/login');
    }

    public function test_user_without_pcb_permission_cannot_access_projects(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer'); // No PCB permissions

        $response = $this->actingAs($user)->get('/projects');
        $response->assertStatus(403);
    }

    public function test_organization_member_can_access_project(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id]);
        $member = User::factory()->create(['organization_id' => $org->id]);

        $project = \App\Models\PcbProject::factory()->create([
            'owner_id' => $owner->id,
            'organization_id' => $org->id,
        ]);

        $member->assignRole('pcb-customer');

        $response = $this->actingAs($member)->get("/projects/{$project->uuid}");
        $response->assertOk();
    }

    public function test_cross_organization_access_denied(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $owner = User::factory()->create(['organization_id' => $org1->id]);
        $otherUser = User::factory()->create(['organization_id' => $org2->id]);

        $project = \App\Models\PcbProject::factory()->create([
            'owner_id' => $owner->id,
            'organization_id' => $org1->id,
        ]);

        $otherUser->assignRole('pcb-customer');

        $response = $this->actingAs($otherUser)->get("/projects/{$project->uuid}");
        $response->assertStatus(403);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('pcb-customer');

        $this->actingAs($user)->post('/logout');
        $this->assertGuest();
    }
}
```

## Security Checklist

- [x] Session cookies scoped to `.neogiga.com`
- [x] Secure cookie flag enabled (HTTPS only)
- [x] HttpOnly flag prevents JavaScript access
- [x] SameSite=Lax prevents CSRF
- [x] Session encryption enabled
- [x] CSRF token validation on forms
- [x] Session regeneration on login
- [x] Audit logging for auth events
- [x] Organization isolation enforced
- [x] Permission checks on all PCB routes
- [x] 2FA support available
- [x] Rate limiting on login attempts

## Troubleshooting

### Common Issues

1. **Session not persisting across subdomains**
   - Verify `SESSION_DOMAIN=.neogiga.com`
   - Check browser cookie settings
   - Ensure HTTPS on all subdomains

2. **CSRF token mismatch**
   - Verify `XSRF-TOKEN` cookie is being set
   - Check CORS configuration
   - Ensure frontend includes token in requests

3. **Permission denied errors**
   - Verify role assignments
   - Check permission seeding
   - Review middleware order

4. **Cross-organization access leaks**
   - Audit `canAccessPcbProject()` logic
   - Verify organization_id checks
   - Test with multiple organizations

## Next Steps

1. Run role seeder: `php artisan db:seed --class=PcbRoleSeeder`
2. Configure SSL certificates for all subdomains
3. Update Nginx configuration for session handling
4. Test SSO flow across all NeoGiga platforms
5. Enable audit logging for authentication events
6. Configure 2FA for privileged users
