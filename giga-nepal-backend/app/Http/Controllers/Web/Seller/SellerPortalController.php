<?php
namespace App\Http\Controllers\Web\Seller;
use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SellerPortalController extends Controller
{
    public function showLogin(SellerContextService $c): View|RedirectResponse
    { if (Auth::check() && $c->vendorFor(Auth::user())) return redirect('/seller'); return view('seller.login'); }
    public function login(Request $r, SellerContextService $c): RedirectResponse
    {
        $r->validate(['email'=>'required|email|max:190','password'=>'required|string|max:120']);
        if (!Auth::attempt($r->only('email','password'),$r->boolean('remember'))) return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        $r->session()->regenerate();
        if (!$c->vendorFor(Auth::user())) { Auth::logout(); return back()->withErrors(['email'=>'No seller account linked.']); }
        return redirect()->intended('/seller');
    }
    public function logout(Request $r): RedirectResponse { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/seller/login'); }
    public function dashboard(Request $r, SellerDashboardService $d): View
    {
        $v = $r->attributes->get('vendor');
        $stats = ['product_count'=>DB::table('products')->where('vendor_id',$v->id)->count(),'order_count'=>DB::table('orders')->where('vendor_id',$v->id)->count()];
        return view('seller.dashboard',compact('v','stats'));
    }
    public function profile(Request $r): View { return view('seller.profile',['v'=>$r->attributes->get('vendor')]); }
    public function updateProfile(Request $r): RedirectResponse
    {
        $v = $r->attributes->get('vendor');
        DB::table('vendors')->where('id',$v->id)->update(['name'=>$r->input('name'),'email'=>$r->input('email'),'phone'=>$r->input('phone'),'website'=>$r->input('website'),'description'=>$r->input('description'),'updated_at'=>now()]);
        return back()->with('status','Profile updated.');
    }
    public function products(Request $r): View { $v=$r->attributes->get('vendor'); $p=DB::table('products')->leftJoin('product_categories as c','c.id','=','products.category_id')->select('products.*','c.name as category_name')->where('vendor_id',$v->id)->orderByDesc('id')->paginate(20); return view('seller.products',compact('v','p')); }
    public function orders(Request $r): View { $v=$r->attributes->get('vendor'); $o=DB::table('orders')->where('vendor_id',$v->id)->orderByDesc('created_at')->paginate(20); return view('seller.orders',compact('v','o')); }
}
