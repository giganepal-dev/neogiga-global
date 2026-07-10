<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasTwoFactorAuthentication
 * 
 * Adds two-factor authentication functionality to User model.
 */
trait HasTwoFactorAuthentication
{
    /**
     * Check if two-factor authentication is enabled for the user.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(string $secret, array $recoveryCodes = []): void
    {
        $this->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(): void
    {
        $this->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Get the two-factor secret.
     */
    public function getTwoFactorSecret(): ?string
    {
        if (!$this->two_factor_secret) {
            return null;
        }

        try {
            return decrypt($this->two_factor_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get recovery codes.
     */
    public function getRecoveryCodes(): array
    {
        if (!$this->two_factor_recovery_codes) {
            return [];
        }

        try {
            $codes = decrypt($this->two_factor_recovery_codes);
            return json_decode($codes, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Replace recovery codes.
     */
    public function replaceRecoveryCodes(array $newCodes): void
    {
        $this->update([
            'two_factor_recovery_codes' => encrypt(json_encode($newCodes)),
        ]);
    }

    /**
     * Verify a two-factor code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->hasTwoFactorEnabled()) {
            return true; // 2FA not enabled, skip verification
        }

        $secret = $this->getTwoFactorSecret();
        if (!$secret) {
            return false;
        }

        // Use Google2FA package for TOTP verification
        $google2fa = new \PragmaRX\Google2FALaravel\Support\Constants();
        
        // Simple TOTP verification (will be enhanced with Google2FA package)
        return $this->verifyTOTP($secret, $code);
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();
        
        if (in_array($code, $codes)) {
            // Remove used recovery code
            $remainingCodes = array_diff($codes, [$code]);
            $this->replaceRecoveryCodes(array_values($remainingCodes));
            return true;
        }

        return false;
    }

    /**
     * Generate a QR code data URI for 2FA setup.
     */
    public function getTwoFactorQrCodeDataUri(string $companyName): string
    {
        $secret = $this->getTwoFactorSecret();
        if (!$secret) {
            throw new \RuntimeException('Two-factor secret not set');
        }

        $email = $this->email;
        $twoFactorUrl = "otpauth://totp/{$companyName}:{$email}?secret={$secret}&issuer={$companyName}";
        
        // Generate QR code (will use bacon/bacon-qr-code package)
        return $twoFactorUrl;
    }

    /**
     * Simple TOTP verification (placeholder until Google2FA package is installed).
     */
    protected function verifyTOTP(string $secret, string $code): bool
    {
        // This will be replaced with proper Google2FA implementation
        // For now, return false to indicate 2FA package needs to be installed
        return false;
    }
}
