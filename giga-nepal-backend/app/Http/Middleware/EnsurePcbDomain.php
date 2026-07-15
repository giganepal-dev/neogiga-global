<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the current request is for the PCB platform domain.
 * More reliable than Route::domain() when Apache proxies don't
 * preserve the Host header correctly.
 */
class EnsurePcbDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $pcbDomain = config('pcb.domain', 'pcb.neogiga.com');
        $host = $request->getHost();

        if ($host !== $pcbDomain && !str_ends_with($host, '.'.$pcbDomain)) {
            // Not a PCB domain request — skip PCB routes
            abort(404);
        }

        return $next($request);
    }
}
