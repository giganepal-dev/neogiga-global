<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Pricing\PricingRule;
use App\Models\Pricing\PriceFloorRule;
use App\Models\Pricing\MarginFloorRule;
use App\Models\Pricing\PriceRoundingRule;
use App\Services\Pricing\PriceSimulator;
use App\Services\Pricing\PricingContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingRuleAdminController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $rules = PricingRule::query()
            ->with('marketplace')
            ->orderBy('priority', 'desc')
            ->orderBy('scope_type')
            ->paginate(50);

        return $this->success($rules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:pricing_rules,code',
            'scope_type' => 'required|string|in:global,marketplace,product,category,brand,manufacturer,seller,reseller,warehouse,country,customer_segment,b2b_account,quantity_tier',
            'marketplace_id' => 'nullable|exists:marketplaces,id',
            'scope_product_id' => 'nullable|exists:products,id',
            'scope_category_id' => 'nullable|exists:product_categories,id',
            'scope_brand_id' => 'nullable|exists:product_brands,id',
            'scope_manufacturer_id' => 'nullable|exists:manufacturers,id',
            'scope_seller_id' => 'nullable|exists:vendors,id',
            'scope_warehouse_id' => 'nullable|exists:warehouses,id',
            'scope_country_id' => 'nullable|exists:countries,id',
            'customer_segment' => 'nullable|string|max:100',
            'min_quantity' => 'nullable|integer|min:0',
            'max_quantity' => 'nullable|integer|min:0|gte:min_quantity',
            'action_type' => 'required|string|in:percentage_markup,fixed_markup,fixed_selling_price,margin_target,freight_markup,payment_fee_markup,currency_adjustment,exchange_rate_buffer,minimum_price,price_floor,maximum_price,price_ceiling,rounding',
            'action_value' => 'required|numeric',
            'action_currency' => 'nullable|string|max:3',
            'priority' => 'required|integer|min:0',
            'stackable' => 'boolean',
            'stop_processing' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'active' => 'boolean',
        ]);

        $validated['approval_status'] = 'approved';
        $validated['version'] = 1;

        $rule = PricingRule::create($validated);

        return $this->success($rule, 201);
    }

    public function show(int $id): JsonResponse
    {
        $rule = PricingRule::with('marketplace')->findOrFail($id);

        return $this->success($rule);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = PricingRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'scope_type' => 'sometimes|string|in:global,marketplace,product,category,brand,manufacturer,seller,reseller,warehouse,country,customer_segment,b2b_account,quantity_tier',
            'marketplace_id' => 'nullable|exists:marketplaces,id',
            'action_type' => 'sometimes|string|in:percentage_markup,fixed_markup,fixed_selling_price,margin_target,freight_markup,payment_fee_markup,currency_adjustment,exchange_rate_buffer,minimum_price,price_floor,maximum_price,price_ceiling,rounding',
            'action_value' => 'sometimes|numeric',
            'action_currency' => 'nullable|string|max:3',
            'priority' => 'sometimes|integer|min:0',
            'stackable' => 'boolean',
            'stop_processing' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'active' => 'boolean',
        ]);

        $validated['version'] = $rule->version + 1;
        $rule->update($validated);

        return $this->success($rule->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $rule = PricingRule::findOrFail($id);
        $rule->delete();

        return $this->success(['deleted' => true]);
    }

    public function simulate(Request $request, PriceSimulator $simulator): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
            'cost_basis' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'customer_segment' => 'nullable|string',
        ]);

        $marketplace = \App\Models\Marketplace\Marketplace::findOrFail($validated['marketplace_id']);

        $ctx = new PricingContext(
            productId: $validated['product_id'],
            marketplace: $marketplace,
            costBasisAmount: $validated['cost_basis'],
            currencyCode: strtoupper($marketplace->currency?->code ?? 'USD'),
            quantity: $validated['quantity'] ?? 1,
            customerSegment: $validated['customer_segment'] ?? null,
        );

        $result = $simulator->simulate($ctx);

        return $this->success($result);
    }

    public function floors(): JsonResponse
    {
        return $this->success([
            'price_floors' => PriceFloorRule::orderBy('id')->get(),
            'margin_floors' => MarginFloorRule::orderBy('id')->get(),
            'rounding_rules' => PriceRoundingRule::orderBy('id')->get(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return $this->success([
            'total_rules' => PricingRule::count(),
            'active_rules' => PricingRule::where('active', true)->count(),
            'approved_rules' => PricingRule::where('approval_status', 'approved')->count(),
            'by_scope' => PricingRule::select('scope_type', DB::raw('count(*) as count'))->groupBy('scope_type')->pluck('count', 'scope_type'),
            'by_action' => PricingRule::select('action_type', DB::raw('count(*) as count'))->groupBy('action_type')->pluck('count', 'action_type'),
        ]);
    }
}
