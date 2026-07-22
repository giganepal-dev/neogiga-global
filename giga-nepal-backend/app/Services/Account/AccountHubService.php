<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountHubService
{
    /** @return array<int, array<string, mixed>> */
    public function roles(User $user, Request $request): array
    {
        $roles = [[
            'key' => 'customer',
            'label' => 'Personal customer',
            'status' => 'approved',
            'url' => '/account',
            'current' => false,
        ]];

        $primary = strtolower((string) ($user->role?->name ?? ''));
        $primaryMap = [
            'institution' => ['institution', 'Institution / B2B', '/b2b'],
            'b2b' => ['institution', 'Institution / B2B', '/b2b'],
            'reseller' => ['reseller', 'Reseller', '/reseller'],
            'seller' => ['seller', 'Marketplace seller', '/seller'],
            'distributor' => ['regional_distributor', 'Regional distributor', '/distributor'],
            'manufacturer' => ['manufacturer', 'Manufacturer', '/manufacturer'],
            'brand_owner' => ['brand_owner', 'Brand owner', '/manufacturer'],
            'warehouse_partner' => ['warehouse_partner', 'Warehouse partner', '/account'],
        ];
        if (isset($primaryMap[$primary])) {
            [$key, $label, $url] = $primaryMap[$primary];
            $this->pushRole($roles, $key, $label, 'approved', $url);
        }

        if (Schema::hasTable('user_account_roles')) {
            DB::table('user_account_roles')
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->get()
                ->each(function ($role) use (&$roles): void {
                    $catalog = $this->roleCatalog()[$role->role_key] ?? null;
                    if ($catalog) {
                        $this->pushRole($roles, $role->role_key, $catalog['label'], 'approved', $catalog['url']);
                    }
                });
        }

        $this->discoverLegacyRoles($roles, $user);

        $allowed = array_column($roles, 'key');
        $active = (string) $request->session()->get('account.active_role', 'customer');
        if (! in_array($active, $allowed, true)) {
            $active = 'customer';
            $request->session()->put('account.active_role', $active);
        }

        return array_map(function (array $role) use ($active): array {
            $role['current'] = $role['key'] === $active;

            return $role;
        }, $roles);
    }

    public function switchRole(User $user, Request $request, string $roleKey): string
    {
        $role = collect($this->roles($user, $request))->firstWhere('key', $roleKey);
        abort_unless($role && $role['status'] === 'approved', 403);

        $request->session()->put('account.active_role', $roleKey);
        $this->audit($user->id, 'role_switched', ['role_key' => $roleKey], $request);

        return (string) $role['url'];
    }

    /** @return array<string, array{label:string,url:string,description:string}> */
    public function roleCatalog(): array
    {
        return [
            'institution' => ['label' => 'Institution / B2B', 'url' => '/b2b', 'description' => 'Company purchasing, teams, credit and purchase-order workflows.'],
            'reseller' => ['label' => 'Reseller', 'url' => '/reseller', 'description' => 'Regional resale catalogue, RFQ bids, orders and territories.'],
            'seller' => ['label' => 'Marketplace seller', 'url' => '/seller', 'description' => 'Marketplace catalogue, inventory, fulfilment and settlements.'],
            'regional_distributor' => ['label' => 'Regional distributor', 'url' => '/distributor', 'description' => 'Regional territory, warehouse, network and commission management.'],
            'global_distributor' => ['label' => 'Global distributor', 'url' => '/distributor', 'description' => 'Multi-market distribution, territory and warehouse operations.'],
            'manufacturer' => ['label' => 'Manufacturer', 'url' => '/manufacturer', 'description' => 'Master catalogue, inventory, regional allocation and product lifecycle.'],
            'brand_owner' => ['label' => 'Brand owner', 'url' => '/manufacturer', 'description' => 'Brand governance, authorizations and product lifecycle.'],
            'warehouse_partner' => ['label' => 'Warehouse / fulfilment partner', 'url' => '/account', 'description' => 'Warehouse stock, fulfilment and regional delivery operations.'],
        ];
    }

    public function owned(string $table, int $userId, int $limit = 50): Collection
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return collect();
        }

        $query = DB::table($table)->where('user_id', $userId);
        if (Schema::hasColumn($table, 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        return $query->limit($limit)->get();
    }

    public function customerProfile(User $user): ?object
    {
        if (! Schema::hasTable('customer_profiles') || ! Schema::hasColumn('customer_profiles', 'user_id')) {
            return null;
        }

        return DB::table('customer_profiles')->where('user_id', $user->id)->first();
    }

    public function audit(?int $userId, string $event, array $metadata, Request $request): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $row = [
            'user_id' => $userId,
            'action' => $event,
            'model_type' => 'account_hub',
            'model_display_name' => 'Unified account',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('audit_logs', 'metadata')) {
            $row['metadata'] = json_encode($metadata, JSON_UNESCAPED_SLASHES);
        }
        if (Schema::hasColumn('audit_logs', 'new_values')) {
            $row['new_values'] = json_encode($metadata, JSON_UNESCAPED_SLASHES);
        }

        $columns = Schema::getColumnListing('audit_logs');
        DB::table('audit_logs')->insert(array_intersect_key($row, array_flip($columns)));
    }

    /** @param array<int, array<string, mixed>> $roles */
    private function discoverLegacyRoles(array &$roles, User $user): void
    {
        if (Schema::hasTable('b2b_account_users') && Schema::hasColumn('b2b_account_users', 'user_id')) {
            $member = DB::table('b2b_account_users')->where('user_id', $user->id)->where('is_active', true)->exists();
            if ($member) {
                $this->pushRole($roles, 'institution', 'Institution / B2B', 'approved', '/b2b');
            }
        }

        if (Schema::hasTable('resellers') && Schema::hasColumn('resellers', 'user_id')) {
            $query = DB::table('resellers')->where('user_id', $user->id);
            if (Schema::hasColumn('resellers', 'is_active')) {
                $query->where('is_active', true);
            }
            if ($query->exists()) {
                $this->pushRole($roles, 'reseller', 'Reseller', 'approved', '/reseller');
            }
        }

        if (Schema::hasTable('seller_applications') && Schema::hasColumn('seller_applications', 'user_id')) {
            $query = DB::table('seller_applications')->where('user_id', $user->id);
            if (Schema::hasColumn('seller_applications', 'status')) {
                $query->where('status', 'approved');
            }
            if ($query->exists()) {
                $this->pushRole($roles, 'seller', 'Marketplace seller', 'approved', '/seller');
            }
        }

        if (Schema::hasTable('distributor_applications') && Schema::hasColumn('distributor_applications', 'user_id')) {
            $query = DB::table('distributor_applications')->where('user_id', $user->id);
            if (Schema::hasColumn('distributor_applications', 'status')) {
                $query->where('status', 'approved');
            }
            if ($query->exists()) {
                $this->pushRole($roles, 'regional_distributor', 'Regional distributor', 'approved', '/distributor');
            }
        }

        if (Schema::hasTable('manufacturers') && Schema::hasColumn('manufacturers', 'user_id')) {
            $query = DB::table('manufacturers')->where('user_id', $user->id);
            if (Schema::hasColumn('manufacturers', 'is_active')) {
                $query->where('is_active', true);
            }
            if ($query->exists()) {
                $this->pushRole($roles, 'manufacturer', 'Manufacturer', 'approved', '/manufacturer');
            }
        }
    }

    /** @param array<int, array<string, mixed>> $roles */
    private function pushRole(array &$roles, string $key, string $label, string $status, string $url): void
    {
        if (collect($roles)->contains('key', $key)) {
            return;
        }
        $roles[] = compact('key', 'label', 'status', 'url') + ['current' => false];
    }
}
