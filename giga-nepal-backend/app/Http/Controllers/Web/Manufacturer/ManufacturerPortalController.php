<?php

namespace App\Http\Controllers\Web\Manufacturer;

use App\Http\Controllers\Controller;
use App\Services\Manufacturer\ManufacturerContextService;
use App\Services\Manufacturer\ManufacturerInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManufacturerPortalController extends Controller
{
    public function showLogin(ManufacturerContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->manufacturerFor(Auth::user())) {
            return redirect('/manufacturer');
        }

        return view('manufacturer.login');
    }

    public function login(Request $r, ManufacturerContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->manufacturerFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No manufacturer account linked.']);
        }

        return redirect()->intended('/manufacturer');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/manufacturer/login');
    }

    public function dashboard(Request $r, ManufacturerInventoryService $inventory): View
    {
        $manufacturer = $r->attributes->get('manufacturer');
        $stats = [
            'product_count' => DB::table('products')->where('manufacturer_id', $manufacturer->id)->count(),
            'active_products' => DB::table('products')->where('manufacturer_id', $manufacturer->id)->whereIn('status', ['active', 'approved', 'published'])->count(),
            'brand_count' => DB::table('product_brands')->where('manufacturer_id', $manufacturer->id)->count(),
        ];
        $inventorySummary = $inventory->globalSummary($manufacturer);
        $allocationSummary = $inventory->allocationSummary($manufacturer);

        return view('manufacturer.dashboard', compact('manufacturer', 'stats', 'inventorySummary', 'allocationSummary'));
    }

    public function profile(Request $r): View
    {
        return view('manufacturer.profile', ['manufacturer' => $r->attributes->get('manufacturer')]);
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $manufacturer = $r->attributes->get('manufacturer');
        $data = [
            'legal_name' => $r->input('legal_name'),
            'official_website' => $r->input('official_website'),
            'country_of_origin' => $r->input('country_of_origin'),
            'overview' => $r->input('overview'),
            'updated_at' => now(),
        ];
        if ($r->hasFile('logo') && $r->file('logo')->isValid()) {
            $data['logo_path'] = $r->file('logo')->store('manufacturers', 'public');
        }
        DB::table('manufacturers')->where('id', $manufacturer->id)->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function products(Request $r): View
    {
        $manufacturer = $r->attributes->get('manufacturer');
        $products = DB::table('products')
            ->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')
            ->select('products.*', 'c.name as category_name')
            ->where('manufacturer_id', $manufacturer->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('manufacturer.products', compact('manufacturer', 'products'));
    }

    public function inventory(Request $r, ManufacturerInventoryService $inventory): View
    {
        $manufacturer = $r->attributes->get('manufacturer');

        return view('manufacturer.inventory', [
            'manufacturer' => $manufacturer,
            'summary' => $inventory->globalSummary($manufacturer),
            'rows' => $inventory->paginateGlobalInventory($manufacturer),
        ]);
    }

    public function syncInventory(Request $r, ManufacturerInventoryService $inventory): RedirectResponse
    {
        $manufacturer = $r->attributes->get('manufacturer');
        $count = $inventory->syncFromCatalog($manufacturer);

        return back()->with('status', "Synced {$count} catalog SKUs into global inventory.");
    }

    public function allocations(Request $r, ManufacturerInventoryService $inventory): View
    {
        $manufacturer = $r->attributes->get('manufacturer');

        return view('manufacturer.allocations', [
            'manufacturer' => $manufacturer,
            'summary' => $inventory->allocationSummary($manufacturer),
            'allocations' => $inventory->paginateAllocations($manufacturer),
            'marketplaces' => $inventory->marketplacesForSelect(),
            'warehouses' => $inventory->warehousesForSelect(),
            'products' => DB::table('products')->where('manufacturer_id', $manufacturer->id)->orderBy('name')->limit(200)->get(['id', 'name', 'sku']),
        ]);
    }

    public function storeAllocation(Request $r, ManufacturerInventoryService $inventory): RedirectResponse
    {
        $manufacturer = $r->attributes->get('manufacturer');
        $data = $r->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'marketplace_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'quantity_allocated' => ['required', 'numeric', 'min:0.0001'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inventory->allocateToRegion($manufacturer, $data);

        return back()->with('status', 'Regional allocation submitted.');
    }
}
