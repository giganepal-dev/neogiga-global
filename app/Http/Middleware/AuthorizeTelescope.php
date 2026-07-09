<?php

namespace Laravel\Telescope\Http\Middleware;

use Laravel\Telescope\Telescope;

class Authorize
{
    /**
     * Authorize access to Telescope.
     */
    public function handle($request, $next)
    {
        // In production, only allow authenticated admins
        if (app()->environment('production')) {
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                return abort(403);
            }
        }

        return $next($request);
    }
}
