<?php
namespace App\Http\Controllers\Web\B2B;
use App\Http\Controllers\Controller;
use App\Services\B2B\B2BContextService;
use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\DB; use Illuminate\View\View;

class B2BPortalController extends Controller
{
    public function showLogin(B2BContextService $c): View|RedirectResponse
    { if (Auth::check() && $c->accountFor(Auth::user())) return redirect('/b2b'); return view('b2b.login'); }
    public function login(Request $r, B2BContextService $c): RedirectResponse
    {
        $r->validate(['email'=>'required|email|max:190','password'=>'required|string|max:120']);
        if (!Auth::attempt($r->only('email','password'),$r->boolean('remember'))) return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');
        $r->session()->regenerate();
        if (!$c->accountFor(Auth::user())) { Auth::logout(); return back()->withErrors(['email'=>'No business account linked.']); }
        return redirect()->intended('/b2b');
    }
    public function logout(Request $r): RedirectResponse { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/b2b/login'); }
    public function dashboard(Request $r): View { return view('b2b.dashboard',['account'=>$r->attributes->get('b2b_account')]); }
    public function orders(Request $r): View {
        $a = $r->attributes->get('b2b_account');
        $orders = DB::table('orders')->where('b2b_account_id',$a->id)->orderByDesc('created_at')->paginate(20);
        return view('b2b.orders',compact('a','orders'));
    }
    public function rfqs(Request $r): View {
        $a = $r->attributes->get('b2b_account');
        $rfqs = DB::table('rfq_requests')->where('b2b_account_id',$a->id)->orderByDesc('created_at')->paginate(20);
        return view('b2b.rfqs',compact('a','rfqs'));
    }
}
