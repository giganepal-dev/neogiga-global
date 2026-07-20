<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pricing\MarginFloorRule;
use App\Models\Pricing\PriceFloorRule;
use App\Models\Pricing\PriceRoundingRule;
use App\Models\Pricing\PricingRule;
use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;

class PricingAdminController extends Controller
{
    /** Show pricing dashboard with all rule types. */
    public function index()
    {
        return view('admin.pricing.index', [
            'rules' => PricingRule::with('marketplace')->orderByDesc('updated_at')->limit(30)->get(),
            'ruleCount' => PricingRule::count(),
            'marginFloors' => MarginFloorRule::with('marketplace')->orderBy('marketplace_id')->get(),
            'priceFloors' => PriceFloorRule::with('marketplace')->orderBy('marketplace_id')->get(),
            'roundingRules' => PriceRoundingRule::with('marketplace')->orderBy('marketplace_id')->get(),
            'marketplaces' => Marketplace::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    /** Store a new pricing rule. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'rule_type' => 'required|string|in:percentage_markup,fixed_markup,fixed_selling_price,margin_target,price_floor,price_ceiling,rounding',
            'scope_type' => 'required|string|in:global,marketplace,category,brand,manufacturer,product',
            'scope_id' => 'nullable|integer',
            'marketplace_id' => 'nullable|integer|exists:marketplaces,id',
            'percentage' => 'nullable|numeric|min:0|max:999',
            'fixed_amount' => 'nullable|numeric|min:0',
            'active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'priority' => 'integer|min:0|max:1000',
        ]);

        PricingRule::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rule_type' => $validated['rule_type'],
            'scope_type' => $validated['scope_type'],
            'scope_id' => $validated['scope_id'] ?? null,
            'marketplace_id' => $validated['marketplace_id'] ?? null,
            'percentage' => $validated['percentage'] ?? null,
            'fixed_amount' => $validated['fixed_amount'] ?? null,
            'active' => $validated['active'] ?? false,
            'approval_status' => 'pending',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'version' => 1,
        ]);

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule created.');
    }

    /** Toggle rule active/inactive. */
    public function toggle(PricingRule $rule)
    {
        $rule->update(['active' => ! $rule->active]);

        return back()->with('success', $rule->active ? 'Rule activated.' : 'Rule deactivated.');
    }

    /** Approve a pending rule. */
    public function approve(PricingRule $rule)
    {
        $rule->update(['approval_status' => 'approved']);

        return back()->with('success', 'Rule approved.');
    }

    /** Delete a rule. */
    public function destroy(PricingRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Rule deleted.');
    }

    /** Store a margin floor rule (minimum margin per marketplace). */
    public function storeMarginFloor(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'min_margin_percent' => 'required|numeric|min:0|max:100',
            'reason' => 'nullable|string|max:500',
        ]);

        MarginFloorRule::updateOrCreate(
            ['marketplace_id' => $validated['marketplace_id']],
            [
                'min_margin_percent' => $validated['min_margin_percent'],
                'reason' => $validated['reason'] ?? null,
            ],
        );

        return back()->with('success', 'Margin floor updated.');
    }

    /** Store a price floor rule. */
    public function storePriceFloor(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'min_price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
        ]);

        PriceFloorRule::updateOrCreate(
            ['marketplace_id' => $validated['marketplace_id']],
            [
                'min_price' => $validated['min_price'],
                'currency_code' => strtoupper($validated['currency_code']),
            ],
        );

        return back()->with('success', 'Price floor updated.');
    }

    /** Store a rounding rule. */
    public function storeRounding(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'nullable|integer|exists:marketplaces,id',
            'method' => 'required|string|in:nearest,up,down',
            'precision' => 'required|numeric|min:0|max:100',
        ]);

        PriceRoundingRule::updateOrCreate(
            ['marketplace_id' => $validated['marketplace_id'] ?? null],
            [
                'method' => $validated['method'],
                'precision' => $validated['precision'],
            ],
        );

        return back()->with('success', 'Rounding rule updated.');
    }
}
