<?php

namespace App\Services\POS;

use App\Models\POS\PosCustomerAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PosCustomerAccountService
{
    public function search(?string $term, ?int $marketplaceId = null, int $limit = 20): array
    {
        if (! Schema::hasTable('pos_customer_accounts')) {
            return [];
        }

        $query = PosCustomerAccount::query()->where('status', 'active');
        if ($marketplaceId) {
            $query->where(function ($inner) use ($marketplaceId) {
                $inner->whereNull('marketplace_id')->orWhere('marketplace_id', $marketplaceId);
            });
        }
        if ($term) {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('account_number', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name')->limit($limit)->get()->all();
    }

    public function create(array $data): PosCustomerAccount
    {
        return PosCustomerAccount::create([
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'account_number' => $data['account_number'] ?? $this->generateAccountNumber(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'store_credit_balance' => $data['store_credit_balance'] ?? 0,
            'status' => 'active',
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function paginate(?int $marketplaceId = null, int $perPage = 25): LengthAwarePaginator
    {
        if (! Schema::hasTable('pos_customer_accounts')) {
            return new Paginator([], 0, $perPage);
        }

        $query = PosCustomerAccount::query()->orderByDesc('id');
        if ($marketplaceId) {
            $query->where(function ($inner) use ($marketplaceId) {
                $inner->whereNull('marketplace_id')->orWhere('marketplace_id', $marketplaceId);
            });
        }

        return $query->paginate($perPage);
    }

    private function generateAccountNumber(): string
    {
        return 'POSC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
