<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_registration_number',
        'pan_number',
        'vat_number',
        'contact_person_name',
        'contact_person_email',
        'contact_person_phone',
        'business_address',
        'country_id',
        'province_id',
        'district_id',
        'city_id',
        'postal_code',
        'preferred_territories',
        'territory_type',
        'business_experience',
        'years_in_business',
        'annual_turnover',
        'currency',
        'interested_categories',
        'company_registration_document',
        'pan_certificate',
        'vat_certificate',
        'citizenship_certificate',
        'tax_clearance_certificate',
        'additional_documents',
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'bank_branch',
        'swift_code',
        'routing_number',
        'status',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'commission_rate',
        'minimum_order_value',
        'target_monthly_sales',
        'ip_address',
        'user_agent',
        'last_login_at',
        'is_active',
    ];

    protected $casts = [
        'preferred_territories' => 'array',
        'interested_categories' => 'array',
        'annual_turnover' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'minimum_order_value' => 'decimal:2',
        'target_monthly_sales' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function territories(): HasMany
    {
        return $this->hasMany(DistributorTerritory::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DistributorActivity::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(DistributorLead::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(DistributorCustomer::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(DistributorCommission::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $admin, array $data = []): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'admin_notes' => $data['admin_notes'] ?? $this->admin_notes,
            'commission_rate' => $data['commission_rate'] ?? $this->commission_rate,
            'minimum_order_value' => $data['minimum_order_value'] ?? $this->minimum_order_value,
            'target_monthly_sales' => $data['target_monthly_sales'] ?? $this->target_monthly_sales,
            'is_active' => true,
        ]);

        return true;
    }

    public function reject(User $admin, string $reason): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
            'admin_notes' => $this->admin_notes,
        ]);

        return true;
    }
}
