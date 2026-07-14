<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interim capability gate for admin.token routes.
 *
 * This is intentionally narrow: it gives the existing shared admin-token API a
 * deny-by-default permission layer until the full admin.user RBAC work lands.
 */
class EnsureAdminTokenPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = $this->configuredPermissions();

        if (! in_array('*', $permissions, true) && ! in_array($permission, $permissions, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function configuredPermissions(): array
    {
        $configured = config('services.admin_api_token_permissions', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        if (! is_array($configured)) {
            return [];
        }

        return collect($configured)
            ->map(fn ($permission) => trim((string) $permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
