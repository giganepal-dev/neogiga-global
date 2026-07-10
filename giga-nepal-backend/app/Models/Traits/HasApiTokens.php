<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens as SanctumHasApiTokens;

trait HasApiTokens
{
    use SanctumHasApiTokens;

    /**
     * Find the user identified by the given Sanctum token.
     * This method is used by Sanctum for token authentication.
     */
    public static function findToken($token)
    {
        if (static::usesSanctum()) {
            $accessToken = static::query()
                ->with('accessToken')
                ->whereRelation('accessToken', 'token', hash($token))
                ->first();

            if ($accessToken && $accessToken->accessToken) {
                return $accessToken;
            }
        }

        return null;
    }

    /**
     * Check if the model uses Sanctum tokens.
     */
    protected static function usesSanctum(): bool
    {
        return in_array(SanctumHasApiTokens::class, class_uses_recursive(static::class));
    }
}
