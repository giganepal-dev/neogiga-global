<?php
namespace App\Http\Controllers\Web\Manufacturer;
use App\Http\Controllers\Controller;
use App\Services\Manufacturer\ManufacturerContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $r->validate(['email'=>'required|email:rfc|max:190','password'=>'required|string|max:120']);
        if (!Auth::attempt($r->only('email','password'), $r->boolean('remember')))
            return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        $r->session()->regenerate();
        if (!$c->manufacturerFor(Auth::user())) { Auth::logout(); return back()->withErrors(['email'=>'No manufacturer account linked.']); }
        return redirect()->intended('/manufacturer');
    }
    public function logout(Request $r): RedirectResponse { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/manufacturer/login'); }
    public function dashboard(Request $r): View { return view('manufacturer.dashboard', ['mfr'=>$r->attributes->get('manufacturer')]); }
    public function products(Request $r): View {
        $mfr = $r->attributes->get('manufacturer');
        $products = DB::table('products')->where('manufacturer_id', $mfr->id)->orderByDesc('id')->paginate(20);
        return view('manufacturer.products', compact('mfr','products'));
    }
}
