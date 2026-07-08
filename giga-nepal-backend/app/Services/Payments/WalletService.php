<?php

namespace App\Services\Payments;

use App\Models\Payments\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Store-credit wallet. Every mutation is row-locked and recorded as an
 * append-only ledger entry with the resulting balance. Debits can never take
 * the balance below zero.
 */
class WalletService
{
    public function walletFor(int $userId, string $currency = 'USD'): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'currency' => $currency, 'status' => 'active']);
    }

    public function credit(int $userId, float $amount, string $type = 'credit', ?int $orderId = null, ?string $reference = null, ?string $note = null): Wallet
    {
        $amount = round(max(0, $amount), 2);
        $this->walletFor($userId);

        return DB::transaction(function () use ($userId, $amount, $type, $orderId, $reference, $note) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            $newBalance = round((float) $wallet->balance + $amount, 2);
            $wallet->update(['balance' => $newBalance]);
            $this->entry($wallet->id, $type, $amount, $newBalance, $orderId, $reference, $note);

            return $wallet->fresh();
        });
    }

    /**
     * Debit up to $amount from the wallet. Returns the amount actually applied
     * (capped at available balance). Never negative, never overspends.
     *
     * @return array{applied:float, balance_after:float}
     */
    public function debit(int $userId, float $amount, ?int $orderId = null, ?string $reference = null, ?string $note = null): array
    {
        return DB::transaction(function () use ($userId, $amount, $orderId, $reference, $note) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet || !$wallet->isSpendable()) {
                return ['applied' => 0.0, 'balance_after' => $wallet ? (float) $wallet->balance : 0.0];
            }

            $applied = round(min(max(0, $amount), (float) $wallet->balance), 2);
            if ($applied <= 0) {
                return ['applied' => 0.0, 'balance_after' => (float) $wallet->balance];
            }

            $newBalance = round((float) $wallet->balance - $applied, 2);
            $wallet->update(['balance' => $newBalance]);
            $this->entry($wallet->id, 'debit', -$applied, $newBalance, $orderId, $reference, $note);

            return ['applied' => $applied, 'balance_after' => $newBalance];
        });
    }

    private function entry(int $walletId, string $type, float $amount, float $balanceAfter, ?int $orderId, ?string $reference, ?string $note): void
    {
        DB::table('wallet_ledger_entries')->insert([
            'wallet_id' => $walletId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'order_id' => $orderId,
            'reference' => $reference,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
