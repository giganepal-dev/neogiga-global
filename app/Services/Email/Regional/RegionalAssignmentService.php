<?php

namespace App\Services\Email\Regional;

use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegionalAssignmentService
{
    protected array $domainRegionMap = [
        'np.neogiga.com' => 'np',
        'in.neogiga.com' => 'in',
        'bt.neogiga.com' => 'bt',
        'bd.neogiga.com' => 'bd',
        'lk.neogiga.com' => 'lk',
        'au.neogiga.com' => 'au',
    ];

    protected array $priorityRules = [
        1 => 'regional_domain',
        2 => 'user_region_relation',
        3 => 'billing_country',
        4 => 'explicit_signup_country',
        5 => 'imported_country_code',
        6 => 'phone_country_code',
        7 => 'ip_geolocation',
        8 => 'admin_import_default',
        9 => 'global_fallback',
    ];

    public function assignSubscriberToRegion(
        EmailSubscriber $subscriber,
        ?string $registrationDomain = null,
        ?User $user = null,
        ?array $billingData = null,
        ?string $explicitCountry = null,
        ?string $importedCountryCode = null,
        ?string $phone = null,
        ?string $ipAddress = null,
        ?string $adminDefaultGroup = null
    ): array {
        $assignmentResult = [
            'country_code' => null,
            'group_id' => null,
            'assignment_source' => null,
            'confidence' => 'low',
            'previous_assignment' => $subscriber->country_code,
        ];

        // Priority 1: Regional domain
        if ($registrationDomain) {
            $regionCode = $this->getRegionFromDomain($registrationDomain);
            if ($regionCode) {
                return $this->assignByCountryCode($subscriber, $regionCode, 'regional_domain', 'high');
            }
        }

        // Priority 2: User region relation
        if ($user && $user->region_id) {
            $regionCode = $this->getCountryCodeFromRegion($user->region_id);
            if ($regionCode) {
                return $this->assignByCountryCode($subscriber, $regionCode, 'user_region', 'high');
            }
        }

        // Priority 3: Billing country
        if (!empty($billingData['country_code'])) {
            return $this->assignByCountryCode($subscriber, $billingData['country_code'], 'billing_country', 'medium');
        }

        // Priority 4: Explicit signup country
        if ($explicitCountry) {
            return $this->assignByCountryCode($subscriber, $explicitCountry, 'explicit_signup', 'high');
        }

        // Priority 5: Imported country code
        if ($importedCountryCode) {
            return $this->assignByCountryCode($subscriber, $importedCountryCode, 'imported_country', 'medium');
        }

        // Priority 6: Phone country code
        if ($phone) {
            $phoneCountryCode = $this->extractCountryFromPhone($phone);
            if ($phoneCountryCode) {
                return $this->assignByCountryCode($subscriber, $phoneCountryCode, 'phone_country', 'medium');
            }
        }

        // Priority 7: IP geolocation
        if ($ipAddress) {
            $ipCountryCode = $this->getCountryFromIP($ipAddress);
            if ($ipCountryCode) {
                return $this->assignByCountryCode($subscriber, $ipCountryCode, 'ip_geolocation', 'low');
            }
        }

        // Priority 8: Admin import default
        if ($adminDefaultGroup) {
            $group = EmailGroup::find($adminDefaultGroup);
            if ($group && $group->country_code) {
                return $this->assignByCountryCode($subscriber, $group->country_code, 'admin_default', 'medium');
            }
        }

        // Priority 9: Global fallback
        return $this->assignToGlobal($subscriber);
    }

    protected function getRegionFromDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        
        // Remove protocol and www
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Check direct match
        if (isset($this->domainRegionMap[$domain])) {
            return $this->domainRegionMap[$domain];
        }

        // Extract subdomain
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            if (strlen($subdomain) === 2 && isset($this->domainRegionMap[$subdomain . '.neogiga.com'])) {
                return $subdomain;
            }
        }

        return null;
    }

    protected function getCountryCodeFromRegion(int $regionId): ?string
    {
        $region = DB::table('regions')->where('id', $regionId)->first();
        
        if (!$region) {
            return null;
        }

        // Assuming regions have a country_code or we map region to primary country
        if (!empty($region->country_code)) {
            return strtolower($region->country_code);
        }

        // Map known NeoGiga regions
        $regionCountryMap = [
            'nepal' => 'np',
            'india' => 'in',
            'bhutan' => 'bt',
            'bangladesh' => 'bd',
            'sri_lanka' => 'lk',
            'australia' => 'au',
        ];

        $regionName = strtolower(str_replace(' ', '_', $region->name ?? ''));
        return $regionCountryMap[$regionName] ?? null;
    }

    protected function extractCountryFromPhone(string $phone): ?string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        $phoneCountryCodes = [
            '+977' => 'np',
            '+91' => 'in',
            '+975' => 'bt',
            '+880' => 'bd',
            '+94' => 'lk',
            '+61' => 'au',
            '+1' => 'us',
            '+44' => 'gb',
        ];

        foreach ($phoneCountryCodes as $code => $country) {
            if (strpos($phone, $code) === 0) {
                return $country;
            }
        }

        return null;
    }

    protected function getCountryFromIP(string $ipAddress): ?string
    {
        // In production, use a real IP geolocation service
        // For now, return null to fall through to global
        // Implementation would use MaxMind GeoIP2 or similar
        
        Log::debug("IP geolocation lookup for: {$ipAddress}");
        
        return null;
    }

    protected function assignByCountryCode(
        EmailSubscriber $subscriber,
        string $countryCode,
        string $source,
        string $confidence
    ): array {
        $countryCode = strtolower($countryCode);

        // Find or create the country group
        $group = EmailGroup::where('country_code', $countryCode)
            ->where('is_active', true)
            ->first();

        if (!$group) {
            // Create default country group if it doesn't exist
            $group = $this->createDefaultCountryGroup($countryCode);
        }

        // Update subscriber
        $previousCountry = $subscriber->country_code;
        $subscriber->update([
            'country_code' => $countryCode,
        ]);

        // Attach to group if not already attached
        if (!$subscriber->groups()->where('email_group_id', $group->id)->exists()) {
            $subscriber->groups()->attach($group->id, [
                'assignment_source' => $source,
                'is_primary' => true,
                'assigned_at' => now(),
                'assigned_by' => auth()->id() ?? null,
            ]);
        } else {
            // Update existing attachment to mark as primary
            $subscriber->groups()->updateExistingPivot($group->id, [
                'is_primary' => true,
                'assignment_source' => $source,
            ]);
        }

        // Log the assignment
        $this->logAssignment($subscriber, $countryCode, $source, $confidence, $previousCountry);

        return [
            'country_code' => $countryCode,
            'group_id' => $group->id,
            'assignment_source' => $source,
            'confidence' => $confidence,
            'previous_assignment' => $previousCountry,
        ];
    }

    protected function assignToGlobal(EmailSubscriber $subscriber): array
    {
        $globalGroup = EmailGroup::where('slug', 'global')
            ->orWhere('name', 'Global')
            ->first();

        if (!$globalGroup) {
            $globalGroup = EmailGroup::create([
                'name' => 'Global',
                'slug' => 'global',
                'description' => 'Global subscribers without specific country assignment',
                'country_code' => 'global',
                'is_active' => true,
                'is_system' => true,
            ]);
        }

        $previousCountry = $subscriber->country_code;
        
        $subscriber->update([
            'country_code' => 'global',
        ]);

        if (!$subscriber->groups()->where('email_group_id', $globalGroup->id)->exists()) {
            $subscriber->groups()->attach($globalGroup->id, [
                'assignment_source' => 'global_fallback',
                'is_primary' => true,
                'assigned_at' => now(),
                'assigned_by' => auth()->id() ?? null,
            ]);
        }

        $this->logAssignment($subscriber, 'global', 'global_fallback', 'low', $previousCountry);

        return [
            'country_code' => 'global',
            'group_id' => $globalGroup->id,
            'assignment_source' => 'global_fallback',
            'confidence' => 'low',
            'previous_assignment' => $previousCountry,
        ];
    }

    protected function createDefaultCountryGroup(string $countryCode): EmailGroup
    {
        $countryNames = [
            'np' => 'Nepal',
            'in' => 'India',
            'bt' => 'Bhutan',
            'bd' => 'Bangladesh',
            'lk' => 'Sri Lanka',
            'au' => 'Australia',
            'us' => 'United States',
            'gb' => 'United Kingdom',
        ];

        $name = $countryNames[$countryCode] ?? strtoupper($countryCode);

        return EmailGroup::create([
            'name' => "{$name} Subscribers",
            'slug' => Str::slug("{$name}-subscribers"),
            'description' => "Subscribers from {$name}",
            'country_code' => $countryCode,
            'is_active' => true,
            'is_system' => false,
        ]);
    }

    protected function logAssignment(
        EmailSubscriber $subscriber,
        string $countryCode,
        string $source,
        string $confidence,
        ?string $previousCountry
    ): void {
        DB::table('email_audit_logs')->insert([
            'auditable_type' => EmailSubscriber::class,
            'auditable_id' => $subscriber->id,
            'action' => 'regional_assignment',
            'old_values' => json_encode(['country_code' => $previousCountry]),
            'new_values' => json_encode([
                'country_code' => $countryCode,
                'assignment_source' => $source,
                'confidence' => $confidence,
            ]),
            'user_id' => auth()->id() ?? null,
            'created_at' => now(),
        ]);

        Log::info("Subscriber {$subscriber->email} assigned to {$countryCode} via {$source}", [
            'subscriber_id' => $subscriber->id,
            'confidence' => $confidence,
            'previous' => $previousCountry,
        ]);
    }

    public function bulkAssignUnassignedSubscribers(int $batchSize = 1000): int
    {
        $unassigned = EmailSubscriber::whereNull('country_code')
            ->orWhere('country_code', 'global')
            ->limit($batchSize)
            ->get();

        $assignedCount = 0;

        foreach ($unassigned as $subscriber) {
            $result = $this->assignSubscriberToRegion($subscriber);
            
            if ($result['assignment_source'] !== 'global_fallback' || $subscriber->country_code !== 'global') {
                $assignedCount++;
            }
        }

        return $assignedCount;
    }

    public function reassignSubscriber(
        EmailSubscriber $subscriber,
        string $newCountryCode,
        ?int $assignedBy = null,
        bool $force = false
    ): array {
        // Don't override explicit admin assignment unless forced
        $currentPrimaryGroup = $subscriber->groups()
            ->wherePivot('is_primary', true)
            ->first();

        if ($currentPrimaryGroup && !$force) {
            // Check if this was an admin assignment
            $pivot = $subscriber->groups()->where('email_group_id', $currentPrimaryGroup->id)->first()?->pivot;
            
            if ($pivot && $pivot->assignment_source === 'admin_manual') {
                throw new \Exception('Cannot override manual admin assignment without force flag');
            }
        }

        return $this->assignByCountryCode($subscriber, $newCountryCode, 'admin_manual', 'high');
    }
}
