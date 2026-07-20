<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Time-based One-Time Password (TOTP) two-factor authentication.
 *
 * Generates secrets, verifies 6-digit codes, and manages recovery codes.
 * Compatible with Google Authenticator, Authy, 1Password, and any
 * RFC 6238 TOTP app.
 */
class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_LENGTH = 10;
    private const WINDOW = 1; // allow ±1 interval for clock drift

    /**
     * Generate a new base32-encoded TOTP secret.
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20); // 160 bits for SHA1
        return $this->base32Encode($bytes);
    }

    /**
     * The otpauth:// URI for QR generation.
     */
    public function qrCodeUri(User $user, string $secret, string $issuer = 'NeoGiga'): string
    {
        $label = rawurlencode("{$issuer}:{$user->email}");

        return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer);
    }

    /**
     * Verify a 6-digit TOTP code against the stored secret.
     */
    public function verify(string $secret, string $code): bool
    {
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $secretBytes = $this->base32Decode($secret);
        $now = (int) floor(time() / 30);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals($this->totp($secretBytes, $now + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a set of one-time recovery codes.
     * @return Collection<int, string>
     */
    public function generateRecoveryCodes(): Collection
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => $this->randomRecoveryCode());
    }

    /**
     * Verify and consume a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        $index = array_search($code, $codes, true);
        if ($index === false) {
            return false;
        }

        // Consume the code (one-time use)
        unset($codes[$index]);
        $user->update(['two_factor_recovery_codes' => array_values($codes)]);

        return true;
    }

    /**
     * Enable 2FA for a user after they confirm enrollment.
     */
    public function enable(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $this->generateRecoveryCodes()->toArray(),
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ]);
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ]);
    }

    /**
     * Check if a user needs to complete 2FA challenge.
     */
    public function challengeRequired(User $user): bool
    {
        return $user->two_factor_enabled
            && $user->two_factor_confirmed_at !== null
            && ! session('2fa.confirmed');
    }

    /**
     * Mark the current session as 2FA-confirmed.
     */
    public function markConfirmed(): void
    {
        session(['2fa.confirmed' => true]);
    }

    // ─── TOTP internals (RFC 6238 / RFC 4226) ────────────────────────

    private function totp(string $secretBytes, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('J', $counter), $secretBytes, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function randomRecoveryCode(): string
    {
        return bin2hex(random_bytes(self::RECOVERY_CODE_LENGTH / 2));
    }

    // ─── Base32 (RFC 4648, no padding) ───────────────────────────────

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private function base32Encode(string $bytes): string
    {
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($bytes); $i++) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $result .= self::ALPHABET[($buffer >> ($bitsLeft - 5)) & 0x1F];
                $bitsLeft -= 5;
            }
        }
        if ($bitsLeft > 0) {
            $result .= self::ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(rtrim($encoded, '='));
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($encoded); $i++) {
            $pos = strpos(self::ALPHABET, $encoded[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $result .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $result;
    }
}
