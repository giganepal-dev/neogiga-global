<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Create default admin user only if not exists.
        // Password comes from SEED_ADMIN_PASSWORD or is randomly generated and
        // printed once — never a hardcoded credential (SECURITY_GAP_REPORT SEC-09).
        if (!User::where('email', 'admin@neogiga.com')->exists()) {
            $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(20);

            User::forceCreate([
                'name' => 'NeoGiga Admin',
                'email' => 'admin@neogiga.com',
                'password' => bcrypt($password),
                'role_id' => Role::where('name', 'super_admin')->value('id'),
                'email_verified_at' => now(),
            ]);

            if (!env('SEED_ADMIN_PASSWORD')) {
                $this->command?->warn("Generated admin password (store it now, it is not saved anywhere): {$password}");
            }
        }

        // Production reference data — deterministic, schema-aligned, always seeded.
        $this->call([
            MarketplaceSeeders\MarketplaceSeeder::class,
            ProductSeeders\CategoryTaxonomySeeder::class,
            AiProjectTemplateSeeder::class,
        ]);

        // Demo/sample catalog — opt-in via SEED_DEMO=true. These seeders still
        // carry model↔migration drift (DB-04) and must not populate production.
        // Enable only in dev after the Phase-1 schema reconciliation.
        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call([
                ProductSeeders\ProductSeeder::class,
                VendorSeeders\VendorSeeder::class,
            ]);
        }
    }
}
