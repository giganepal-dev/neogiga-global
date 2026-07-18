<?php
namespace App\Http\Controllers\Web\Manufacturer;
use App\Http\Controllers\Controller;
use App\Services\Manufacturer\ManufacturerContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ManufacturerPortalController extends Controller
{
    public function showLogin(ManufacturerContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->manufacturerFor(Auth::user())) return redirect('/manufacturer');
        return view('manufacturer.login');
    }

    public function login(Request $r, ManufacturerContextService $c): RedirectResponse
    {
        $r->validate(['email'=>'required|email|max:190','password'=>'required|string|max:120']);
        if (!Auth::attempt($r->only('email','password'),$r->boolean('remember')))
            return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        $r->session()->regenerate();
        if (!$c->manufacturerFor(Auth::user())) { Auth::logout(); return back()->withErrors(['email'=>'No manufacturer account linked.']); }
        return redirect()->intended('/manufacturer');
    }

    public function logout(Request $r): RedirectResponse { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/manufacturer/login'); }

    public function dashboard(Request $r): View
    {
        $mfr = $r->attributes->get('manufacturer');
        $stats = [
            'product_count' => DB::table('products')->where('manufacturer_id', $mfr->id)->count(),
            'active_products' => DB::table('products')->where('manufacturer_id', $mfr->id)->whereIn('status', ['active','approved','published'])->count(),
            'brand_count' => DB::table('product_brands')->where('manufacturer_id', $mfr->id)->count(),
        ];
        return view('manufacturer.dashboard', compact('mfr', 'stats'));
    }

    public function profile(Request $r): View
    {
        $mfr = $r->attributes->get('manufacturer');
        return view('manufacturer.profile', compact('mfr'));
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $mfr = $r->attributes->get('manufacturer');
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
        DB::table('manufacturers')->where('id', $mfr->id)->update($data);
        return back()->with('status', 'Profile updated.');
    }

    public function products(Request $r): View
    {
        $mfr = $r->attributes->get('manufacturer');
        $products = DB::table('products')
            ->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')
            ->select('products.*', 'c.name as category_name')
            ->where('manufacturer_id', $mfr->id)
            ->orderByDesc('id')
            ->paginate(20);
        return view('manufacturer.products', compact('mfr', 'products'));
    }
}
