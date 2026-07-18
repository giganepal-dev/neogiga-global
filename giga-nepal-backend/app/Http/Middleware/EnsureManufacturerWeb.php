<?php
namespace App\Http\Middleware;
use App\Services\Manufacturer\ManufacturerContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureManufacturerWeb
{
    public function __construct(private readonly ManufacturerContextService $context) {}
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) return redirect('/manufacturer/login');
        $mfr = $this->context->manufacturerFor($user);
        if (! $mfr) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/manufacturer/login')->withErrors(['email' => 'No manufacturer account linked.']);
        }
        $request->attributes->set('manufacturer', $mfr);
        return $next($request);
    }
}
