<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interim gate for /api/admin/* routes (SEC-01/SEC-02).
 *
 * Requires the X-Admin-Token header to match ADMIN_API_TOKEN or the
 * sha256 hash stored in ADMIN_API_TOKEN_HASH. If neither env var is configured,
 * ALL admin requests are refused (fail closed).
 *
 * This is a Phase-0 placeholder: replace with Sanctum + RBAC policies
 * (Blueprint §14–15) in Phase 1. Do NOT ship multi-user admin on this.
 */
class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.admin_api_token');
        $configuredHash = config('services.admin_api_token_hash');
        $provided = (string) $request->header('X-Admin-Token');

        if (! $this->matchesConfiguredToken($provided, $configured, $configuredHash)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }

    private function matchesConfiguredToken(string $provided, mixed $configured, mixed $configuredHash): bool
    {
        if ($provided === '') {
            return false;
        }

        if (is_string($configuredHash) && $configuredHash !== '') {
            return hash_equals($configuredHash, hash('sha256', $provided));
        }

        return is_string($configured)
            && $configured !== ''
            && hash_equals($configured, $provided);
    }
}
