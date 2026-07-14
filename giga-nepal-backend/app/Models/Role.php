<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    /** @var list<string>|null */
    private ?array $resolvedPermissionKeys = null;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $permission): bool
    {
        if ($this->resolvedPermissionKeys === null) {
            $permissions = array_values(array_filter(array_map('strval', $this->permissions ?? [])));

            if (Schema::hasTable('role_permissions') && Schema::hasTable('permissions')) {
                $assigned = DB::table('role_permissions as rp')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->where('rp.role_id', $this->getKey())
                    ->where('p.is_active', true)
                    ->pluck('p.key')
                    ->map(static fn ($key): string => (string) $key)
                    ->all();
                $permissions = array_values(array_unique(array_merge($permissions, $assigned)));
            }

            $this->resolvedPermissionKeys = $permissions;
        }

        return in_array('*', $this->resolvedPermissionKeys, true)
            || in_array($permission, $this->resolvedPermissionKeys, true);
    }
}
