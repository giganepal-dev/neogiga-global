<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(Request $request): View
    {
        $slugs = array_filter(explode(',', (string) $request->query('p', '')));
        $products = !empty($slugs)
            ? Product::with(['brand', 'category', 'images' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_primary')->limit(1)])
                ->whereIn('slug', array_slice($slugs, 0, 4))
                ->get()
                ->sortBy(fn ($p) => array_search($p->slug, $slugs))
                ->values()
            : collect();

        $specs = $products->isNotEmpty()
            ? \Illuminate\Support\Facades\DB::table('product_specs')
                ->whereIn('product_id', $products->pluck('id'))
                ->orderBy('sort_order')
                ->get()
                ->groupBy('attribute_name')
            : collect();

        return view('frontend.compare.index', compact('products', 'specs', 'slugs'));
    }
}
