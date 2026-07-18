<?php
namespace App\Http\Middleware;
use App\Services\B2B\B2BContextService;
use Closure; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureB2BWeb
{
    public function __construct(private readonly B2BContextService $c) {}
    public function handle(Request $r, Closure $next): Response
    {
        $u = Auth::user();
        if (!$u) return redirect('/b2b/login');
        $a = $this->c->accountFor($u);
        if (!$a) { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/b2b/login')->withErrors(['email'=>'No business account linked.']); }
        $r->attributes->set('b2b_account', $a);
        return $next($r);
    }
}
