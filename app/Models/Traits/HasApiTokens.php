<?php

namespace NeoGiga\Models\Traits;

use Laravel\Sanctum\HasApiTokens as SanctumHasApiTokens;
use Laravel\Sanctum\NewAccessToken;

trait HasApiTokens
{
    use SanctumHasApiTokens;

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @param  \DateTimeInterface|null  $expiresAt
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function createToken(
        string $name,
        array $abilities = ['*'],
        ?\DateTimeInterface $expiresAt = null
    ): NewAccessToken {
        $token = $this->createPersonalAccessToken($name, $abilities, $expiresAt);

        return new NewAccessToken(
            $token,
            $token->getKey() . '|' . $token->plainTextToken
        );
    }

    /**
     * Revoke all tokens for a specific device.
     *
     * @param  string  $deviceFingerprint
     * @return int
     */
    public function revokeTokensForDevice(string $deviceFingerprint): int
    {
        return $this->tokens()
            ->where('device_fingerprint', $deviceFingerprint)
            ->update(['revoked' => true]);
    }

    /**
     * Get active tokens count.
     *
     * @return int
     */
    public function getActiveTokenCount(): int
    {
        return $this->tokens()->where('revoked', false)->count();
    }

    /**
     * Check if user has a specific ability via token.
     *
     * @param  string  $ability
     * @return bool
     */
    public function hasTokenAbility(string $ability): bool
    {
        $token = $this->currentAccessToken();

        if (!$token) {
            return false;
        }

        foreach ($token->abilities as $storedAbility) {
            if ($storedAbility === '*' || $storedAbility === $ability) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set token expiration.
     *
     * @param  \DateTimeInterface  $expiresAt
     * @return void
     */
    public function setTokenExpiration(\DateTimeInterface $expiresAt): void
    {
        $token = $this->currentAccessToken();

        if ($token) {
            $token->update(['expires_at' => $expiresAt]);
        }
    }
}
