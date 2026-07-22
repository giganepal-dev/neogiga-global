<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerApplicationService
{
    public function approve(int $applicationId, int $reviewerId): void
    {
        DB::transaction(function () use ($applicationId, $reviewerId): void {
            $application = DB::table('account_applications')->lockForUpdate()->find($applicationId);
            if (! $application || ! in_array($application->status, ['submitted', 'under_review', 'needs_information'], true)) {
                throw new RuntimeException('Application is not available for approval.');
            }

            $sourceId = $this->provision($application, $reviewerId);
            DB::table('user_account_roles')->updateOrInsert([
                'user_id' => $application->user_id,
                'role_key' => $application->role_key,
                'marketplace_id' => $application->marketplace_id,
            ], [
                'status' => 'approved', 'source_type' => 'account_application', 'source_id' => $sourceId ?: $application->id,
                'approved_at' => now(), 'approved_by' => $reviewerId, 'updated_at' => now(), 'created_at' => now(),
            ]);
            DB::table('account_applications')->where('id', $application->id)->update([
                'status' => 'approved', 'reviewed_by' => $reviewerId, 'reviewed_at' => now(), 'approved_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('account_application_documents')->where('account_application_id', $application->id)->where('status', 'pending')->update([
                'status' => 'approved', 'reviewed_by' => $reviewerId, 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            $this->event($application->id, $reviewerId, 'approved', $application->status, 'approved');
        });
    }

    public function review(int $applicationId, int $reviewerId, string $status, string $notes): void
    {
        if (! in_array($status, ['under_review', 'needs_information', 'rejected'], true)) {
            throw new RuntimeException('Unsupported review status.');
        }
        DB::transaction(function () use ($applicationId, $reviewerId, $status, $notes): void {
            $application = DB::table('account_applications')->lockForUpdate()->find($applicationId);
            if (! $application || $application->status === 'approved') {
                throw new RuntimeException('Approved applications cannot be changed here.');
            }
            DB::table('account_applications')->where('id', $applicationId)->update([
                'status' => $status, 'review_notes' => $notes, 'reviewed_by' => $reviewerId,
                'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            $this->event($applicationId, $reviewerId, $status, $application->status, $status, $notes);
        });
    }

    private function provision(object $application, int $reviewerId): ?int
    {
        $user = DB::table('users')->find($application->user_id);
        if (! $user) {
            throw new RuntimeException('Applicant user no longer exists.');
        }
        $slug = Str::slug($application->company_name).'-'.$application->id;

        return match ($application->role_key) {
            'institution' => $this->institution($application, $user, $slug),
            'reseller' => $this->reseller($application, $user),
            'seller' => $this->seller($application, $user, $slug),
            'regional_distributor', 'global_distributor' => $this->distributor($application, $user, $slug, $reviewerId),
            'manufacturer', 'brand_owner' => $this->manufacturer($application, $user, $slug),
            default => null,
        };
    }

    private function institution(object $a, object $user, string $slug): int
    {
        $this->required('b2b_accounts');
        $id = DB::table('b2b_accounts')->insertGetId([
            'name' => $a->company_name, 'slug' => $slug, 'type' => 'corporate', 'status' => 'active',
            'email' => $user->email, 'phone' => $a->contact_phone, 'pan_vat_number' => $a->tax_number,
            'marketplace_id' => $a->marketplace_id, 'metadata' => json_encode(['account_application_id' => $a->id]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('b2b_account_users')->insert([
            'b2b_account_id' => $id, 'user_id' => $a->user_id, 'name' => $user->name, 'email' => $user->email,
            'role' => 'owner', 'permissions' => json_encode(['*']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function reseller(object $a, object $user): int
    {
        $this->required('resellers');

        return DB::table('resellers')->insertGetId($this->columns('resellers', [
            'user_id' => $a->user_id, 'company_name' => $a->company_name, 'trading_name' => $a->company_name,
            'registration_number' => $a->registration_number, 'tax_number' => $a->tax_number,
            'region' => $a->territory, 'contact_person' => $user->name, 'email' => $user->email,
            'phone' => $a->contact_phone, 'website' => $a->website, 'status' => 'approved', 'is_active' => true,
            'home_marketplace_id' => $a->marketplace_id, 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function seller(object $a, object $user, string $slug): int
    {
        $this->required('vendors');

        return DB::table('vendors')->insertGetId([
            'user_id' => $a->user_id, 'name' => $a->company_name, 'slug' => $slug, 'email' => $user->email,
            'phone' => $a->contact_phone, 'website' => $a->website, 'description' => $a->business_description,
            'tax_number' => $a->tax_number, 'registration_number' => $a->registration_number,
            'country_id' => $a->country_id ?? null, 'operating_scope' => $a->operating_scope ?? 'country',
            'status' => 'active', 'type' => 'company', 'is_verified' => true,
            'metadata' => json_encode(['account_application_id' => $a->id]), 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function distributor(object $a, object $user, string $slug, int $reviewerId): int
    {
        $this->required('distributors');

        return DB::table('distributors')->insertGetId([
            'user_id' => $a->user_id, 'name' => $a->company_name, 'slug' => $slug, 'email' => $user->email,
            'phone' => $a->contact_phone, 'type' => $a->role_key === 'global_distributor' ? 'global' : 'regional',
            'country_id' => $a->country_id ?? null, 'operating_scope' => $a->operating_scope ?? ($a->role_key === 'global_distributor' ? 'global' : 'country'),
            'status' => 'active', 'approved_by' => $reviewerId, 'approved_at' => now(),
            'metadata' => json_encode(['account_application_id' => $a->id, 'territory' => $a->territory]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function manufacturer(object $a, object $user, string $slug): int
    {
        $this->required('manufacturers');

        return DB::table('manufacturers')->insertGetId($this->columns('manufacturers', [
            'user_id' => $a->user_id, 'name' => $a->company_name, 'slug' => $slug, 'legal_name' => $a->legal_name,
            'official_website' => $a->website, 'overview' => $a->business_description, 'is_active' => true,
            'is_verified' => true, 'source_name' => 'NeoGiga partner application',
            'source_url' => url('/admin/partner-approvals'), 'source_page_url' => url('/admin/partner-approvals'),
            'imported_at' => now(), 'data_year' => now()->year, 'license_note' => 'Applicant-provided company data',
            'confidence_level' => 'admin_verified', 'original_raw_value' => $a->company_name,
            'normalized_value' => Str::lower($a->company_name), 'metadata' => json_encode(['account_application_id' => $a->id]),
            'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function required(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException("Required partner table {$table} is unavailable.");
        }
    }

    private function columns(string $table, array $values): array
    {
        return array_intersect_key($values, array_flip(Schema::getColumnListing($table)));
    }

    private function event(int $applicationId, int $actorId, string $type, ?string $from, string $to, ?string $notes = null): void
    {
        DB::table('account_application_events')->insert([
            'account_application_id' => $applicationId, 'actor_user_id' => $actorId, 'event_type' => $type,
            'from_status' => $from, 'to_status' => $to, 'notes' => $notes, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
