<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\RegionalCommerceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartPageController extends Controller
{
    public function show(Request $request): View
    {
        $cart = app(RegionalCommerceService::class)->applyCartEstimates($this->activeCart($request));

        return view('frontend.cart.show', [
            'cart' => $cart->load(['items.product.brand', 'items.product.category']),
            'routes' => app(RegionalCommerceService::class)->cartRoutes($cart),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $product = Product::published()->find($data['product_id']);
        if (! $product) {
            return back()->with('error', 'Product is not available for cart.');
        }

        $cart = $this->activeCart($request);
        $pricing = $this->regionalPrice($request, $product);
        $price = $pricing['price'];

        $item = $cart->items()->where('product_id', $product->id)->whereNull('variant_id')->first();
        if ($item) {
            $item->forceFill([
                'quantity' => min(500, (int) $item->quantity + (int) $data['quantity']),
                'unit_price' => $price,
                'metadata' => $pricing['metadata'],
            ])->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $price,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'metadata' => $pricing['metadata'],
            ]);
        }

        app(RegionalCommerceService::class)->applyCartEstimates($cart);

        return redirect('/cart')->with('status', 'Product added to cart.');
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $cart = $this->activeCart($request);
        $cartItem = $cart->items()->whereKey($item)->first();
        if (! $cartItem) {
            return back()->with('error', 'Cart item not found.');
        }

        $cartItem->forceFill(['quantity' => $data['quantity']])->save();
        app(RegionalCommerceService::class)->applyCartEstimates($cart);

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, int $item): RedirectResponse
    {
        $cart = $this->activeCart($request);
        $cart->items()->whereKey($item)->delete();
        app(RegionalCommerceService::class)->applyCartEstimates($cart);

        return back()->with('status', 'Cart item removed.');
    }

    public function checkout(Request $request): View
    {
        $cart = app(RegionalCommerceService::class)->applyCartEstimates($this->activeCart($request));

        return view('frontend.cart.checkout', [
            'cart' => $cart->load(['items.product.brand']),
            'countries' => DB::table('countries')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'routes' => app(RegionalCommerceService::class)->cartRoutes($cart),
        ]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:180'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:manual,bank_transfer,cod'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ]);

        $cart = $this->activeCart($request)->load(['items.product']);
        if ($cart->items->isEmpty()) {
            return redirect('/cart')->with('error', 'Cart is empty.');
        }

        $order = DB::transaction(function () use ($cart, $data) {
            app(RegionalCommerceService::class)->applyCartEstimates($cart, (int) ($data['country_id'] ?? 0));
            $cart->refresh()->load(['items.product']);

            $order = Order::create([
                'order_number' => 'NG-WEB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'user_id' => null,
                'marketplace_id' => $cart->marketplace_id,
                'status' => 'pending',
                'currency_code' => $cart->currency_code,
                'subtotal' => $cart->subtotal,
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $cart->shipping_total,
                'grand_total' => $cart->grand_total,
                'amount_paid' => 0,
                'amount_due' => $cart->grand_total,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'billing_address' => $this->addressPayload($data),
                'shipping_address' => $this->addressPayload($data),
                'customer_notes' => $data['customer_notes'] ?? null,
                'metadata' => [
                    'cart_id' => $cart->id,
                    'source' => 'public_session_checkout',
                    'customer_email' => $data['email'],
                    'company' => $data['company'] ?? null,
                    'provider_call_made' => false,
                    'regional_estimate' => [
                        'tax_zone_id' => data_get($cart->metadata, 'tax_zone_id'),
                        'delivery_zone_id' => data_get($cart->metadata, 'delivery_zone_id'),
                        'estimated_delivery_days' => data_get($cart->metadata, 'estimated_delivery_days'),
                        'note' => data_get($cart->metadata, 'regional_estimate_note'),
                    ],
                ],
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'product_name' => $item->product?->name ?? 'Unknown product',
                    'product_sku' => $item->product?->sku ?? 'UNKNOWN',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_amount' => $item->tax_amount,
                    'discount_amount' => $item->discount_amount,
                    'total_price' => $item->total,
                    'metadata' => $item->metadata,
                ]);
            }

            Payment::create([
                'payment_number' => 'PAY-WEB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'order_id' => $order->id,
                'marketplace_id' => $order->marketplace_id,
                'payment_method' => $data['payment_method'],
                'payment_gateway' => 'manual',
                'amount' => $order->grand_total,
                'currency_code' => $order->currency_code,
                'status' => 'pending',
                'payment_details' => ['provider_call_made' => false, 'requires_manual_confirmation' => true],
            ]);

            $cart->forceFill(['is_active' => false])->save();

            return $order;
        });

        $request->session()->forget('cart_id');

        return redirect('/checkout/thank-you/'.$order->order_number)->with('status', 'Order placed for manual confirmation.');
    }

    public function thankYou(string $orderNumber): View
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('frontend.cart.thank-you', compact('order'));
    }

    private function activeCart(Request $request): Cart
    {
        $cartId = $request->session()->get('cart_id');
        $cart = $cartId ? Cart::active()->where('id', $cartId)->first() : null;

        if (! $cart) {
            $marketplaceContext = app(GlobalMarketplaceContextService::class)->context($request);
            $marketplace = $marketplaceContext['current'] ?? null;

            $cart = Cart::create([
                'session_id' => $request->session()->getId(),
                'marketplace_id' => $marketplace?->id,
                'currency_code' => $marketplaceContext['currency_code'] ?? 'USD',
                'is_active' => true,
                'expires_at' => now()->addDays(30),
                'metadata' => [
                    'source' => 'public_session_cart',
                    'marketplace_code' => strtolower((string) ($marketplace?->code ?? 'global')),
                    'country_id' => $marketplaceContext['country_id'] ?? null,
                ],
            ]);
            $request->session()->put('cart_id', $cart->id);
        }

        return $cart;
    }

    private function regionalPrice(Request $request, Product $product): array
    {
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context($request);
        $marketplace = $marketplaceContext['current'] ?? null;
        $price = null;
        $currency = $marketplaceContext['currency_code'] ?? 'USD';
        $source = 'global_catalog';

        if ($marketplace) {
            $overlay = MarketplaceProductPrice::query()
                ->where('product_id', $product->id)
                ->where('marketplace_id', $marketplace->id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('sale_start_date')->orWhere('sale_start_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('sale_end_date')->orWhere('sale_end_date', '>=', now());
                })
                ->orderByRaw('CASE WHEN sale_price IS NOT NULL THEN 0 ELSE 1 END')
                ->first();

            if ($overlay) {
                $price = (float) ($overlay->sale_price ?: $overlay->base_price ?: 0);
                $currency = $overlay->currency_code ?: $currency;
                $source = 'marketplace_overlay';
            }
        }

        if ($price === null) {
            $price = (float) ($product->sale_price ?: $product->base_price ?: 0);
        }

        return [
            'price' => $price,
            'metadata' => [
                'source' => 'public_frontend',
                'price_note' => $price > 0 ? $source : 'rfq_price_pending',
                'marketplace_id' => $marketplace?->id,
                'marketplace_code' => strtolower((string) ($marketplace?->code ?? 'global')),
                'currency_code' => $currency,
                'country_id' => $marketplaceContext['country_id'] ?? null,
            ],
        ];
    }

    private function addressPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }
}
