# NeoGiga Panel System - Phase 2 Implementation Command

## Executive Summary

**Status**: Foundation Complete → Ready for Enhancement Phase  
**Timeline**: 2-3 weeks for Phase 2 delivery  
**Priority**: P0 Blockers first, then P1 enhancements  

---

## Current State Assessment ✅

### Completed Foundation (Phase 1)

| Component | Status | Details |
|-----------|--------|---------|
| **Database Schema** | ✅ Complete | 144 migrations, distributor/seller/admin tables |
| **Models** | ✅ Complete | 80+ models with relationships |
| **Controllers** | ✅ Complete | 93 controllers across all panels |
| **Services** | ✅ Complete | Dashboard, Context, Business Logic services |
| **Routes** | ✅ Complete | API v1 routes with auth middleware |
| **Auth System** | ✅ Partial | Token-based auth, needs Sanctum upgrade |

### Key Existing Features

**Admin Panel:**
- AdminConsoleController (dashboard metrics, navigation, settings)
- ProductAdminController (approval workflow)
- VendorAdminController, InventoryAdminController
- Marketing, Finance, B2B, LMS admin controllers
- ImportExportController, PaymentAdminController

**Seller Panel:**
- SellerDashboardController (overview, sales, orders, inventory, payouts)
- SellerProductController, SellerInventoryController
- SellerOrderController, SellerPayoutController
- SellerProfileController, SellerSupportTicketController

**Distributor Panel:**
- DistributorDashboardController (territory, leads, customers overview)
- DistributorApplicationController (public applications)
- DistributorResourceController

---

## Phase 2 Implementation Plan

### P0 Blockers (Week 1) - CRITICAL PATH

#### Task 2.1: Laravel Sanctum Integration + RBAC Policies
**Priority**: P0 Blocker | **Effort**: 2 days | **Risk**: High

**Objective**: Replace custom token auth with Laravel Sanctum + role-based access control

**Implementation Steps:**

1. **Install Sanctum**
```bash
cd /workspace/giga-nepal-backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

2. **Update User Model** (`app/Models/User.php`)
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // Add HasApiTokens trait
}
```

3. **Create Role & Permission Models** (if not exists)
```bash
php artisan make:model Role
php artisan make:model Permission
php artisan make:migration create_role_user_table
php artisan make:migration create_permission_role_table
```

4. **Define Roles** (config/roles.php)
```php
return [
    'global_admin' => ['*'],
    'admin' => ['admin.*', 'commerce.*'],
    'vendor_admin' => ['seller.*', 'products.*'],
    'regional_ops' => ['distributor.*', 'territory.*'],
    'catalog_ops' => ['products.view', 'categories.view'],
    'finance' => ['finance.*', 'payouts.*'],
    'customer' => ['cart.*', 'orders.*', 'profile.*'],
];
```

5. **Create Middleware** (`app/Http/Middleware/HasRole.php`)
```php
public function handle($request, Closure $next, ...$roles)
{
    if (!$request->user() || !$request->user()->hasAnyRole($roles)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    return $next($request);
}
```

6. **Update Routes** (`routes/api.php`)
```php
// Replace admin.token middleware with sanctum + role
Route::middleware(['auth:sanctum', 'role:global_admin'])->prefix('admin')->group(function () {
    // Admin routes
});

Route::middleware(['auth:sanctum', 'role:vendor_admin'])->prefix('seller')->group(function () {
    // Seller routes
});

Route::middleware(['auth:sanctum', 'role:regional_ops'])->prefix('distributor')->group(function () {
    // Distributor routes
});
```

7. **Add Role Methods to User Model**
```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}

public function hasRole(string $role): bool
{
    return $this->roles()->where('name', $role)->exists();
}

public function hasAnyRole(array $roles): bool
{
    return $this->roles()->whereIn('name', $roles)->exists();
}

public function hasPermission(string $permission): bool
{
    return $this->roles()->whereHas('permissions', function ($q) use ($permission) {
        $q->where('name', $permission);
    })->exists();
}
```

**Acceptance Criteria:**
- [ ] Sanctum tokens working for all user types
- [ ] Role-based middleware protecting routes
- [ ] Permission checks on resource operations
- [ ] Admin can assign/revoke roles
- [ ] Tests pass for auth flows

---

#### Task 2.2: Inventory Soft-Reservation System (15-min TTL)
**Priority**: P0 Blocker | **Effort**: 2-3 days | **Risk**: Medium

**Objective**: Prevent overselling with time-bound inventory reservation

**Implementation Steps:**

1. **Update StockReservation Model** (`app/Models/StockReservation.php`)
```php
class StockReservation extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'quantity', 'status',
        'session_id', 'user_id', 'cart_token',
        'reserved_at', 'expires_at', 'confirmed_at', 'cancelled_at'
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function release(): void
    {
        $this->update(['status' => 'released', 'cancelled_at' => now()]);
        // Increment available stock
        $inventory = Inventory::findOrFail($this->inventory_id);
        $inventory->increment('available_quantity', $this->quantity);
    }
}
```

2. **Create Reservation Service** (`app/Services/Inventory/ReservationService.php`)
```php
class ReservationService
{
    const TTL_MINUTES = 15;

    public function reserve(int $productId, int $quantity, ?User $user = null, ?string $cartToken = null): StockReservation
    {
        DB::transaction(function () use ($productId, $quantity, $user, $cartToken) {
            $inventory = Inventory::where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            throw_if($inventory->available_quantity < $quantity, new InsufficientStockException());

            $inventory->decrement('available_quantity', $quantity);
            $inventory->increment('reserved_quantity', $quantity);

            return StockReservation::create([
                'product_id' => $productId,
                'inventory_id' => $inventory->id,
                'quantity' => $quantity,
                'user_id' => $user?->id,
                'session_id' => Session::getId(),
                'cart_token' => $cartToken,
                'status' => 'reserved',
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });
    }

    public function confirm(StockReservation $reservation): void
    {
        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $inventory = $reservation->inventory;
        $inventory->decrement('reserved_quantity', $reservation->quantity);
        $inventory->decrement('total_quantity', $reservation->quantity);
    }

    public function cancel(StockReservation $reservation): void
    {
        $reservation->release();
    }

    public function releaseExpiredReservations(): int
    {
        $expired = StockReservation::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $reservation) {
            $reservation->release();
        }

        return $expired->count();
    }
}
```

3. **Create Console Command** (`php artisan make:command ReleaseExpiredReservations`)
```php
class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release-expired';
    protected $description = 'Release expired inventory reservations';

    public function handle(ReservationService $service): int
    {
        $count = $service->releaseExpiredReservations();
        $this->info("Released {$count} expired reservations");
        return 0;
    }
}
```

4. **Schedule Command** (`app/Console/Kernel.php`)
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('reservations:release-expired')->everyMinute();
}
```

5. **Update CartController** to use reservations
```php
public function checkout(Request $request, ReservationService $reservation)
{
    $cart = $request->user()->cart;
    
    foreach ($cart->items as $item) {
        $reservation->reserve(
            $item->product_id,
            $item->quantity,
            $request->user(),
            $cart->token
        );
    }

    return response()->json([
        'message' => 'Items reserved for 15 minutes',
        'expires_at' => now()->addMinutes(15)->toIso8601String(),
    ]);
}
```

**Acceptance Criteria:**
- [ ] Reservations created on cart checkout
- [ ] 15-minute TTL enforced
- [ ] Auto-release job running every minute
- [ ] Stock correctly updated on confirm/cancel
- [ ] Race conditions prevented with DB locks

---

#### Task 2.3: Payment Gateway Integration (eSewa, Khalti, Stripe, COD)
**Priority**: P0 Blocker | **Effort**: 3-4 days | **Risk**: High

**Objective**: Multi-gateway payment support with Nepal compliance

**Implementation Steps:**

1. **Create Payment Gateway Interface** (`app/Contracts/PaymentGatewayInterface.php`)
```php
interface PaymentGatewayInterface
{
    public function initiate(array $data): PaymentInitiationResponse;
    public function verify(string $transactionId): PaymentVerificationResponse;
    public function refund(string $transactionId, float $amount): RefundResponse;
    public function getGatewayCode(): string;
}
```

2. **Implement eSewa Gateway** (`app/Services/Payment/EsewaGateway.php`)
```php
class EsewaGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $merchantCode,
        private readonly string $secretKey,
        private readonly bool $testMode
    ) {}

    public function initiate(array $data): PaymentInitiationResponse
    {
        $payload = [
            'amount' => $data['amount'],
            'tax_amount' => $data['tax_amount'] ?? 0,
            'total_amount' => $data['total_amount'],
            'transaction_uuid' => $data['order_id'],
            'product_service_charge' => 0,
            'product_delivery_charge' => 0,
            'success_url' => route('payment.esewa.success'),
            'failure_url' => route('payment.esewa.failure'),
            'signed_field_names' => 'total_amount,transaction_uuid,product_service_charge,product_delivery_charge',
            'signature' => hash_hmac('sha256', $this->buildSignatureString($data), $this->secretKey),
        ];

        $url = $this->testMode 
            ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
            : 'https://epay.esewa.com.np/api/epay/main/v2/form';

        return new PaymentInitiationResponse($url, 'POST', $payload);
    }

    public function verify(string $transactionId): PaymentVerificationResponse
    {
        // Call eSewa verification API
    }

    public function getGatewayCode(): string
    {
        return 'esewa';
    }
}
```

3. **Implement Khalti Gateway** (`app/Services/Payment/KhaltiGateway.php`)
```php
class KhaltiGateway implements PaymentGatewayInterface
{
    public function initiate(array $data): PaymentInitiationResponse
    {
        $response = Http::withHeaders([
            'Authorization' => 'Key ' . config('payment.khalti.secret_key'),
            'Content-Type' => 'application/json',
        ])->post(config('payment.khalti.url'), [
            'return_url' => route('payment.khalti.success'),
            'website_url' => config('app.url'),
            'amount' => $data['total_amount'] * 100, // Khalti uses paisa
            'purchase_order_id' => $data['order_id'],
            'purchase_order_name' => $data['description'] ?? 'Order Payment',
            'customer_info' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'],
            ],
        ]);

        $result = $response->json();

        return new PaymentInitiationResponse(
            $result['payment_url'],
            'GET',
            []
        );
    }
}
```

4. **Payment Factory** (`app/Services/Payment/PaymentGatewayFactory.php`)
```php
class PaymentGatewayFactory
{
    public static function create(string $gateway): PaymentGatewayInterface
    {
        return match($gateway) {
            'esewa' => app(EsewaGateway::class),
            'khalti' => app(KhaltiGateway::class),
            'stripe' => app(StripeGateway::class),
            'cod' => app(CodGateway::class),
            default => throw new InvalidGatewayException(),
        };
    }
}
```

5. **Update Payment Model** (`app/Models/Payment.php`)
```php
class Payment extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'gateway', 'transaction_id',
        'amount', 'currency', 'status', 'metadata',
        'initiated_at', 'authorized_at', 'captured_at', 'failed_at', 'refunded_at'
    ];

    const STATUS_INITIATED = 'initiated';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_CAPTURED = 'captured';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
}
```

6. **Payment Controller** (`app/Http/Controllers/Api/Payment/PaymentController.php`)
```php
class PaymentController extends Controller
{
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'gateway' => 'required|in:esewa,khalti,stripe,cod',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $gateway = PaymentGatewayFactory::create($validated['gateway']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => $validated['gateway'],
            'amount' => $order->total_amount,
            'currency' => 'NPR',
            'status' => Payment::STATUS_INITIATED,
            'initiated_at' => now(),
        ]);

        $response = $gateway->initiate([
            'order_id' => $payment->id,
            'amount' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'customer_name' => $order->user->name,
            'customer_email' => $order->user->email,
            'customer_phone' => $order->user->phone,
        ]);

        return response()->json([
            'payment_id' => $payment->id,
            'gateway_url' => $response->url,
            'method' => $response->method,
            'payload' => $response->payload,
        ]);
    }

    public function callback(Request $request, string $gateway): JsonResponse
    {
        $gateway = PaymentGatewayFactory::create($gateway);
        $verification = $gateway->verify($request->input('transaction_id'));

        $payment = Payment::findOrFail($request->input('order_id'));
        
        if ($verification->success) {
            $payment->update([
                'status' => Payment::STATUS_CAPTURED,
                'transaction_id' => $verification->transactionId,
                'captured_at' => now(),
                'metadata' => $verification->metadata,
            ]);

            $payment->order->update(['status' => 'paid']);

            return redirect()->route('payment.success', ['payment' => $payment->id]);
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failed_at' => now(),
        ]);

        return redirect()->route('payment.failure');
    }
}
```

7. **Add Routes** (`routes/api.php`)
```php
Route::middleware('auth:sanctum')->prefix('v1/payment')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate']);
    Route::get('/{payment}', [PaymentController::class, 'show']);
    
    // Gateway callbacks (no auth needed)
    Route::any('/callback/esewa', [PaymentController::class, 'callback'])->with('gateway', 'esewa');
    Route::any('/callback/khalti', [PaymentController::class, 'callback'])->with('gateway', 'khalti');
    Route::any('/callback/stripe', [PaymentController::class, 'callback'])->with('gateway', 'stripe');
});
```

**Acceptance Criteria:**
- [ ] All 4 gateways functional in sandbox mode
- [ ] Payment state machine working (initiated → authorized → captured)
- [ ] Webhook/callback handling secure
- [ ] NP VAT 13% calculated correctly
- [ ] Failed payments handled gracefully

---

### P1 Enhancements (Week 2)

#### Task 2.4: Admin Dashboard Analytics Enhancement
**Priority**: P1 | **Effort**: 2-3 days

**Features to Add:**
- Real-time revenue charts (daily/weekly/monthly)
- Geographic distribution map (Nepal districts)
- Top products/vendors/customers
- Low stock alerts dashboard
- Pending approvals queue
- System health metrics

#### Task 2.5: Seller Product Creation Wizard (8 Steps)
**Priority**: P1 | **Effort**: 3-4 days

**Steps:**
1. Basic Info (name, category, brand)
2. Specifications (dynamic based on category template)
3. Pricing (base price, discount, tax)
4. Inventory (SKU, quantity, warehouse)
5. Media (images, videos, datasheets)
6. SEO (meta title, description, keywords)
7. Shipping (dimensions, weight, regions)
8. Review & Submit

**Features:**
- Auto-save drafts every 30 seconds
- Validation per step
- Progress indicator
- Preview before submit

#### Task 2.6: Distributor CRM Enhancement
**Priority**: P1 | **Effort**: 3-4 days

**Features:**
- Lead pipeline (new → contacted → qualified → converted)
- Customer relationship tracking
- Territory performance dashboard
- Commission calculator
- Activity timeline
- Document management

---

## Testing Strategy

### Unit Tests
```bash
php artisan test --filter=AuthTest
php artisan test --filter=ReservationTest
php artisan test --filter=PaymentTest
```

### Feature Tests
- Auth flow (register, login, logout, password reset)
- Role-based access control
- Inventory reservation lifecycle
- Payment gateway integration (mocked)
- Dashboard data accuracy

### Integration Tests
- Full checkout flow (cart → reserve → payment → order)
- Admin approval workflows
- Distributor lead-to-customer conversion

---

## Security Considerations

1. **Authentication**: Sanctum tokens with expiration
2. **Authorization**: RBAC with least privilege
3. **Payment Security**: PCI-DSS compliance via gateway tokens
4. **Data Validation**: Server-side validation on all inputs
5. **Rate Limiting**: Stricter limits on payment/auth endpoints
6. **Audit Logging**: All state-changing actions logged

---

## Deployment Checklist

- [ ] Environment variables configured (.env)
- [ ] Database migrations run
- [ ] Queue worker configured (Redis)
- [ ] Scheduler enabled (cron)
- [ ] SSL certificates installed
- [ ] Payment gateway credentials configured
- [ ] Monitoring setup (logs, metrics, alerts)
- [ ] Backup strategy implemented

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Auth Response Time | < 200ms | APM monitoring |
| Payment Success Rate | > 95% | Gateway analytics |
| Inventory Accuracy | 100% | Audit reconciliation |
| Dashboard Load Time | < 2s | Frontend metrics |
| Zero Overselling | 0 incidents | Order audits |

---

## Next Actions

1. **Immediate**: Start Task 2.1 (Sanctum + RBAC)
2. **Day 3-4**: Task 2.2 (Inventory Reservation)
3. **Day 5-8**: Task 2.3 (Payment Gateways)
4. **Week 2**: P1 Enhancements (2.4-2.6)

**Estimated Completion**: 10-12 business days

---

## Appendix: File Structure

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── Admin/
│   │   │   ├── Seller/
│   │   │   ├── Distributor/
│   │   │   └── Payment/
│   │   │       └── PaymentController.php
│   └── Middleware/
│       └── HasRole.php
├── Models/
│   ├── Payment.php
│   ├── StockReservation.php
│   └── Role.php
├── Services/
│   ├── Inventory/
│   │   └── ReservationService.php
│   └── Payment/
│       ├── EsewaGateway.php
│       ├── KhaltiGateway.php
│       ├── StripeGateway.php
│       ├── CodGateway.php
│       └── PaymentGatewayFactory.php
└── Console/Commands/
    └── ReleaseExpiredReservations.php
```

---

**Document Version**: 1.0  
**Created**: 2025-07-09  
**Status**: Ready for Implementation  
