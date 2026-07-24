<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Cart;
use App\Models\Marketplace\Product;
use App\Services\Ai\DatabaseAiTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AI Commerce endpoints (Blueprint §13, §29).
 *
 * The tool layer is ready (App\Services\Ai\AiToolsContract +
 * DatabaseAiTools) — all price/stock/product facts come from the
 * database, never from a model. These endpoints use DatabaseAiTools
 * to provide real product search, BOM creation, and cart operations
 * without requiring a paid AI API.
 */
class AiCommerceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DatabaseAiTools $tools = new DatabaseAiTools(),
    ) {}

    public function createSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'context' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $sessionId = 'AIS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));

        $session = [
            'session_id' => $sessionId,
            'user_id' => $request->user()->id,
            'marketplace_id' => $validated['marketplace_id'] ?? null,
            'context' => $validated['context'] ?? null,
            'status' => 'active',
            'created_at' => now()->toDateTimeString(),
            'messages' => [],
        ];

        return $this->success($session, 201);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $marketplaceId = $validated['marketplace_id'] ?? null;
        $userMessage = $validated['message'];

        $searchResults = $this->tools->searchProducts($userMessage, $marketplaceId, 5);

        $suggestions = [];
        foreach ($searchResults['items'] as $item) {
            $suggestions[] = [
                'product_id' => $item['id'],
                'name' => $item['name'],
                'sku' => $item['sku'] ?? null,
                'price' => $item['price'] ?? null,
                'brand' => $item['brand']['name'] ?? null,
                'category' => $item['category']['name'] ?? null,
            ];
        }

        $reply = $searchResults['total'] > 0
            ? "I found {$searchResults['total']} products matching your request."
            : "I couldn't find exact matches. Try refining your search.";

        return $this->success([
            'session_id' => $validated['session_id'] ?? null,
            'user_message' => $userMessage,
            'reply' => $reply,
            'products' => $suggestions,
            'total_results' => $searchResults['total'],
        ]);
    }

    public function buildBom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $marketplaceId = $request->input('marketplace_id');

        $resolved = [];
        foreach ($validated['items'] as $item) {
            $result = $this->tools->resolveBOMLine(
                $item['name'],
                $item['quantity'],
                $marketplaceId,
            );
            $resolved[] = $result;
        }

        $totalPrice = 0;
        $allResolved = true;
        foreach ($resolved as $line) {
            if ($line['price'] !== null && isset($line['price']['amount'])) {
                $totalPrice += (float) $line['price']['amount'] * $line['quantity'];
            } else {
                $allResolved = false;
            }
        }

        return $this->success([
            'description' => $validated['description'],
            'lines' => $resolved,
            'total_price' => round($totalPrice, 2),
            'all_lines_resolved' => $allResolved,
        ]);
    }

    public function addBomToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'marketplace_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $userId = $request->user()->id;
        $marketplaceId = $validated['marketplace_id'] ?? null;

        $cart = Cart::firstOrCreate(
            ['user_id' => $userId, 'is_active' => true],
            [
                'marketplace_id' => $marketplaceId,
                'currency_code' => 'USD',
                'expires_at' => now()->addDays(30),
            ],
        );

        $added = 0;
        $errors = [];

        foreach ($validated['items'] as $item) {
            $product = Product::query()->published()->find($item['product_id']);
            if (! $product) {
                $errors[] = ['product_id' => $item['product_id'], 'error' => 'Product not available'];
                continue;
            }

            $existing = $cart->items()
                ->where('product_id', $item['product_id'])
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'quantity' => $existing->quantity + $item['quantity'],
                ])->save();
            } else {
                $cart->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->sale_price ?? $product->base_price ?? 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'metadata' => ['source' => 'ai_bom'],
                ]);
            }
            $added++;
        }

        $cart->calculateTotal();
        $cart->refresh()->load('items');

        return $this->success([
            'cart_id' => $cart->id,
            'items_added' => $added,
            'errors' => $errors,
            'cart' => $cart->only(['id', 'subtotal', 'tax_total', 'grand_total', 'currency_code']),
        ]);
    }

    public function createPosInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $subtotal = 0;
        $items = [];

        foreach ($validated['items'] as $item) {
            $product = Product::query()->find($item['product_id']);
            $lineTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $product?->name ?? 'Unknown',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => round($lineTotal, 2),
            ];
        }

        $invoiceNumber = 'POS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));

        return $this->success([
            'invoice_number' => $invoiceNumber,
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'tax_amount' => 0,
            'total_amount' => round($subtotal, 2),
            'currency_code' => 'USD',
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'status' => 'issued',
            'issued_at' => now()->toDateTimeString(),
        ], 201);
    }
}
