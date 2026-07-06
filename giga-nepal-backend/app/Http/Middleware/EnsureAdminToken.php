<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interim gate for /api/admin/* routes (SEC-01/SEC-02).
 *
 * Requires the X-Admin-Token header to match ADMIN_API_TOKEN. If the env var
 * is not configured, ALL admin requests are refused (fail closed).
 *
 * This is a Phase-0 placeholder: replace with Sanctum + RBAC policies
 * (Blueprint §14–15) in Phase 1. Do NOT ship multi-user admin on this.
 */
class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.admin_api_token');

        if (!is_string($configured) || $configured === ''
            || !hash_equals($configured, (string) $request->header('X-Admin-Token'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
