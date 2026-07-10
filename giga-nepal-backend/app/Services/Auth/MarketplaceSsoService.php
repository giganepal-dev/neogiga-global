<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceSsoService
{
    public function issue(User $user, string $marketplaceCode, Request $request, string $returnPath = '/'): ?string
    {
        $edition = app(GlobalMarketplaceContextService::class)->marketplaceForPreference($marketplaceCode);
        if (! $edition || empty($edition['domain'])) {
            return null;
        }

        $token = Str::random(80);
        $returnPath = $this->safeReturnPath($returnPath);

        DB::table('sso_handoffs')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'source_host' => $request->getHost(),
            'target_host' => $edition['domain'],
            'return_path' => $returnPath,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'expires_at' => now()->addMinutes(3),
            'metadata' => json_encode([
                'target_marketplace' => $edition['code'],
                'advisory' => 'One-time SSO handoff token. Not a reusable API token.',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return 'https://' . $edition['domain'] . '/sso/consume?token=' . urlencode($token);
    }

    public function consume(string $token, Request $request): ?User
    {
        $tokenHash = hash('sha256', $token);

        return DB::transaction(function () use ($tokenHash, $request) {
            $handoff = DB::table('sso_handoffs')
                ->where('token_hash', $tokenHash)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $handoff || strtolower($handoff->target_host) !== strtolower($request->getHost())) {
                return null;
            }

            DB::table('sso_handoffs')->where('id', $handoff->id)->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);

            return User::find($handoff->user_id);
        });
    }

    public function returnPathForToken(string $token): string
    {
        $handoff = DB::table('sso_handoffs')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        return $this->safeReturnPath((string) ($handoff->return_path ?? '/'));
    }

    private function safeReturnPath(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }
}
