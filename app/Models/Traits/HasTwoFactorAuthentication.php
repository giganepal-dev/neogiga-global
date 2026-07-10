<?php

namespace NeoGiga\Models\Traits;

use PragmaRX\Google2FALaravel\Support\Auth;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

trait HasTwoFactorAuthentication
{
    /**
     * Enable or disable 2FA.
     *
     * @var bool
     */
    protected $twoFactorEnabled = false;

    /**
     * Check if two-factor authentication is enabled.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled ?? false;
    }

    /**
     * Enable two-factor authentication.
     *
     * @param  string|null  $secret
     * @return void
     */
    public function enableTwoFactor(?string $secret = null): void
    {
        $this->two_factor_secret = $secret ?? $this->generateTwoFactorSecret();
        $this->two_factor_enabled = true;
        $this->two_factor_confirmed_at = now();
        $this->save();
    }

    /**
     * Disable two-factor authentication.
     *
     * @return void
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_enabled = false;
        $this->two_factor_confirmed_at = null;
        $this->two_factor_recovery_codes = null;
        $this->save();
    }

    /**
     * Generate a new two-factor secret.
     *
     * @return string
     */
    public function generateTwoFactorSecret(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get the QR code URL for two-factor setup.
     *
     * @param  string|null  $secret
     * @return string
     */
    public function getTwoFactorQrUrl(?string $secret = null): string
    {
        $companyName = config('app.name', 'NeoGiga');
        $email = $this->email;
        $secret = $secret ?? $this->two_factor_secret;

        return "otpauth://totp/{$companyName}:{$email}?secret={$secret}&issuer={$companyName}";
    }

    /**
     * Get the QR code as SVG.
     *
     * @param  string|null  $qrUrl
     * @return string
     */
    public function getTwoFactorQrCodeSvg(?string $qrUrl = null): string
    {
        $qrUrl = $qrUrl ?? $this->getTwoFactorQrUrl();

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrUrl);
    }

    /**
     * Verify the two-factor code.
     *
     * @param  string  $code
     * @return bool
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->hasTwoFactorEnabled() || !$this->two_factor_secret) {
            return false;
        }

        // Use Google2FA to verify
        $google2fa = app('pragmarx.google2fa');
        
        return $google2fa->verifyKey(
            $this->two_factor_secret,
            $code
        );
    }

    /**
     * Generate recovery codes.
     *
     * @return array
     */
    public function generateTwoFactorRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        $this->two_factor_recovery_codes = json_encode(array_map(function ($code) {
            return hash('sha256', $code);
        }, $codes));

        $this->save();

        return $codes;
    }

    /**
     * Consume a recovery code.
     *
     * @param  string  $code
     * @return bool
     */
    public function consumeTwoFactorRecoveryCode(string $code): bool
    {
        if (!$this->two_factor_recovery_codes) {
            return false;
        }

        $hashedCode = hash('sha256', strtoupper($code));
        $recoveryCodes = json_decode($this->two_factor_recovery_codes, true);

        if (in_array($hashedCode, $recoveryCodes)) {
            // Remove used code
            $recoveryCodes = array_diff($recoveryCodes, [$hashedCode]);
            $this->two_factor_recovery_codes = json_encode(array_values($recoveryCodes));
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Check if recovery codes are available.
     *
     * @return bool
     */
    public function hasTwoFactorRecoveryCodes(): bool
    {
        return !empty($this->two_factor_recovery_codes);
    }

    /**
     * Get remaining recovery codes count.
     *
     * @return int
     */
    public function getRemainingRecoveryCodesCount(): int
    {
        if (!$this->two_factor_recovery_codes) {
            return 0;
        }

        return count(json_decode($this->two_factor_recovery_codes, true));
    }
}
