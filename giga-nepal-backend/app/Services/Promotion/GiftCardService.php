<?php

namespace App\Services\Promotion;

use App\Models\Promotion\GiftCard;
use App\Models\Promotion\GiftCardTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gift-card issuance and redemption. Balance is a row-locked, append-only
 * ledger — a redeem can never take the balance below zero and never double-spend.
 */
class GiftCardService
{
    public function issue(float $amount, string $currency = 'USD', ?string $email = null, ?\DateTimeInterface $expiresAt = null): GiftCard
    {
        return DB::transaction(function () use ($amount, $currency, $email, $expiresAt) {
            $amount = round(max(0, $amount), 2);
            $card = GiftCard::create([
                'code' => $this->uniqueCode(),
                'initial_balance' => $amount,
                'current_balance' => $amount,
                'currency' => $currency,
                'status' => 'active',
                'issued_to_email' => $email,
                'expires_at' => $expiresAt,
            ]);

            $card->transactions()->create([
                'type' => 'issue',
                'amount' => $amount,
                'balance_after' => $amount,
                'note' => 'Issued',
                'created_at' => now(),
            ]);

            return $card;
        });
    }

    /**
     * @return array{found:bool, spendable:bool, balance:float, currency:?string, status:?string}
     */
    public function check(string $code): array
    {
        $card = GiftCard::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])->first();

        if (!$card) {
            return ['found' => false, 'spendable' => false, 'balance' => 0.0, 'currency' => null, 'status' => null];
        }

        return [
            'found' => true,
            'spendable' => $card->isSpendable(),
            'balance' => (float) $card->current_balance,
            'currency' => $card->currency,
            'status' => $card->status,
        ];
    }

    /**
     * Redeem up to $requested from the card against an order. Row-locked so
     * concurrent redemptions cannot overspend. Returns the amount actually applied.
     *
     * @return array{applied:float, balance_after:float, currency:string}
     */
    public function redeem(string $code, float $requested, ?int $userId = null, ?int $orderId = null): array
    {
        return DB::transaction(function () use ($code, $requested, $userId, $orderId) {
            $card = GiftCard::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])->lockForUpdate()->first();

            if (!$card || !$card->isSpendable()) {
                throw new \RuntimeException('Gift card is not redeemable.');
            }

            $applied = round(min(max(0, $requested), (float) $card->current_balance), 2);
            if ($applied <= 0) {
                throw new \RuntimeException('Nothing to redeem.');
            }

            $newBalance = round((float) $card->current_balance - $applied, 2);

            $card->update([
                'current_balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'redeemed' : 'active',
            ]);

            $card->transactions()->create([
                'type' => 'redeem',
                'amount' => -$applied,
                'balance_after' => $newBalance,
                'order_id' => $orderId,
                'user_id' => $userId,
                'note' => 'Redeemed at checkout',
                'created_at' => now(),
            ]);

            return ['applied' => $applied, 'balance_after' => $newBalance, 'currency' => $card->currency];
        });
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(Str::random(12));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }
}
