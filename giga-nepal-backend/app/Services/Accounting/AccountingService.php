<?php

namespace App\Services\Accounting;

use App\Models\Erp\DocumentNumberService;
use Illuminate\Support\Facades\DB;

/**
 * Double-entry accounting service.
 *
 * Every journal entry must balance (∑debits = ∑credits). Entries are
 * atomic — all lines succeed or none do. Once posted, entries are
 * immutable (voidable but not editable).
 *
 * ponytail: single-currency entries per journal. Multi-currency journals
 * deferred until cross-border settlements demand them.
 */
class AccountingService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
    ) {}

    /**
     * Create a journal entry with multiple lines. Validates balance before saving.
     *
     * @param array{entry_date:string, reference_type?:string, reference_id?:int,
     *   description?:string, lines:array<int, array{account_id:int, debit?:float,
     *   credit?:float, marketplace_id?:int, description?:string}>} $data
     * @return int Entry ID
     * @throws \InvalidArgumentException if debits ≠ credits
     */
    public function createEntry(array $data, bool $autoPost = true): int
    {
        return DB::transaction(function () use ($data, $autoPost) {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($data['lines'] as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }

            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new \InvalidArgumentException(sprintf(
                    'Journal entry does not balance: debit %.2f ≠ credit %.2f',
                    $totalDebit,
                    $totalCredit,
                ));
            }

            if ($totalDebit === 0.0 && $totalCredit === 0.0) {
                throw new \InvalidArgumentException('Journal entry has zero-value lines.');
            }

            $entryId = DB::table('accounting_entries')->insertGetId([
                'journal_number' => $this->numbers->next('JE', 'JE-'),
                'entry_date' => $data['entry_date'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $autoPost ? 'posted' : 'draft',
                'posted_by' => $autoPost ? (auth()->id() ?? null) : null,
                'posted_at' => $autoPost ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['lines'] as $line) {
                DB::table('accounting_entry_lines')->insert([
                    'entry_id' => $entryId,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'marketplace_id' => $line['marketplace_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $entryId;
        });
    }

    /**
     * Record a POS sale: debit Cash/Payment, credit Sales Revenue, debit COGS, credit Inventory.
     */
    public function recordPosSale(int $saleId, float $amount, float $costOfGoods, string $paymentMethod, ?int $marketplaceId = null): int
    {
        $lines = [
            // Debit: Cash or Payment Gateway Clearing (asset ↑)
            [
                'account_id' => $this->accountIdByCode($paymentMethod === 'cash' ? '1000' : '1400'),
                'debit' => $amount,
                'marketplace_id' => $marketplaceId,
                'description' => "POS sale #{$saleId} — {$paymentMethod}",
            ],
            // Credit: Sales Revenue (revenue ↑)
            [
                'account_id' => $this->accountIdByCode('4000'),
                'credit' => $amount,
                'marketplace_id' => $marketplaceId,
                'description' => "POS sale #{$saleId}",
            ],
        ];

        if ($costOfGoods > 0) {
            // Debit: COGS (expense ↑)
            $lines[] = [
                'account_id' => $this->accountIdByCode('5000'),
                'debit' => $costOfGoods,
                'marketplace_id' => $marketplaceId,
                'description' => "COGS — POS sale #{$saleId}",
            ];
            // Credit: Inventory (asset ↓)
            $lines[] = [
                'account_id' => $this->accountIdByCode('1200'),
                'credit' => $costOfGoods,
                'marketplace_id' => $marketplaceId,
                'description' => "Inventory relief — POS sale #{$saleId}",
            ];
        }

        return $this->createEntry([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'pos_sale',
            'reference_id' => $saleId,
            'description' => "POS Sale #{$saleId} — {$paymentMethod}",
            'lines' => $lines,
        ]);
    }

    /**
     * Record a refund: debit Sales Returns, credit Cash/Payment.
     */
    public function recordRefund(int $refundId, float $amount, float $restockCost, string $method, ?int $marketplaceId = null): int
    {
        $lines = [
            // Debit: Sales Returns (contra-revenue ↑)
            [
                'account_id' => $this->accountIdByCode('8100'),
                'debit' => $amount,
                'marketplace_id' => $marketplaceId,
                'description' => "Refund #{$refundId}",
            ],
            // Credit: Cash or Payment Gateway (asset ↓)
            [
                'account_id' => $this->accountIdByCode($method === 'cash' ? '1000' : '1400'),
                'credit' => $amount,
                'marketplace_id' => $marketplaceId,
                'description' => "Refund #{$refundId} — {$method}",
            ],
        ];

        if ($restockCost > 0) {
            // Debit: Inventory (asset ↑ — restock)
            $lines[] = [
                'account_id' => $this->accountIdByCode('1200'),
                'debit' => $restockCost,
                'marketplace_id' => $marketplaceId,
                'description' => "Restock — refund #{$refundId}",
            ];
            // Credit: COGS (expense ↓)
            $lines[] = [
                'account_id' => $this->accountIdByCode('5000'),
                'credit' => $restockCost,
                'marketplace_id' => $marketplaceId,
                'description' => "COGS reversal — refund #{$refundId}",
            ];
        }

        return $this->createEntry([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'refund',
            'reference_id' => $refundId,
            'description' => "Refund #{$refundId}",
            'lines' => $lines,
        ]);
    }

    /**
     * Post a draft entry (makes it permanent).
     */
    public function post(int $entryId): void
    {
        DB::table('accounting_entries')->where('id', $entryId)->update([
            'status' => 'posted',
            'posted_by' => auth()->id(),
            'posted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Void a posted entry (creates reversing entry).
     */
    public function void(int $entryId, string $reason): int
    {
        return DB::transaction(function () use ($entryId, $reason) {
            $entry = DB::table('accounting_entries')->findOrFail($entryId);
            $lines = DB::table('accounting_entry_lines')->where('entry_id', $entryId)->get();

            $reversal = [];
            foreach ($lines as $line) {
                $reversal[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,   // flip debit↔credit
                    'credit' => $line->debit,
                    'marketplace_id' => $line->marketplace_id,
                    'description' => "VOID of JE #{$entry->journal_number}: {$reason}",
                ];
            }

            $voidEntryId = $this->createEntry([
                'entry_date' => now()->toDateString(),
                'reference_type' => 'void',
                'reference_id' => $entryId,
                'description' => "VOID — {$entry->journal_number}: {$reason}",
                'lines' => $reversal,
            ]);

            DB::table('accounting_entries')->where('id', $entryId)->update([
                'status' => 'voided',
                'updated_at' => now(),
            ]);

            return $voidEntryId;
        });
    }

    /**
     * Get account balance at a point in time.
     */
    public function accountBalance(int $accountId, ?string $asOf = null): float
    {
        $query = DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ael.entry_id', '=', 'ae.id')
            ->where('ael.account_id', $accountId)
            ->where('ae.status', 'posted');

        if ($asOf) {
            $query->where('ae.entry_date', '<=', $asOf);
        }

        $totals = $query->selectRaw('SUM(ael.debit) as total_debit, SUM(ael.credit) as total_credit')->first();

        $account = DB::table('chart_of_accounts')->find($accountId);

        if ($account->normal_balance === 'credit') {
            return ((float) $totals->total_credit) - ((float) $totals->total_debit);
        }

        return ((float) $totals->total_debit) - ((float) $totals->total_credit);
    }

    /**
     * Trial balance: all accounts with their net balances.
     */
    public function trialBalance(?string $asOf = null): array
    {
        return DB::table('chart_of_accounts as coa')
            ->select('coa.code', 'coa.name', 'coa.type', 'coa.normal_balance')
            ->selectRaw('COALESCE(SUM(ael.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(ael.credit), 0) as total_credit')
            ->leftJoin('accounting_entry_lines as ael', 'coa.id', '=', 'ael.account_id')
            ->leftJoin('accounting_entries as ae', function ($join) use ($asOf) {
                $join->on('ael.entry_id', '=', 'ae.id')
                    ->where('ae.status', '=', 'posted');
                if ($asOf) {
                    $join->where('ae.entry_date', '<=', $asOf);
                }
            })
            ->where('coa.is_active', true)
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type', 'coa.normal_balance')
            ->orderBy('coa.code')
            ->get()
            ->toArray();
    }

    private function accountIdByCode(string $code): int
    {
        return (int) DB::table('chart_of_accounts')->where('code', $code)->value('id');
    }
}
