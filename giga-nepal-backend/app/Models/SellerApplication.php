<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SellerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'contact_person',
        'email',
        'phone',
        'country',
        'state',
        'city',
        'business_address',
        'pan_number',
        'vat_number',
        'company_registration_number',
        'website_url',
        'product_categories',
        'brand_names',
        'estimated_monthly_volume',
        'additional_info',
        'document_pan',
        'document_company_reg',
        'document_tax_certificate',
        'document_identity',
        'status',
        'admin_notes',
        'reviewed_by',
        'approved_at',
    ];

    protected $casts = [
        'product_categories' => 'array',
        'brand_names' => 'array',
        'estimated_monthly_volume' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve($adminUser, $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $adminUser->id,
            'admin_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    public function reject($adminUser, $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $adminUser->id,
            'admin_notes' => $notes,
        ]);
    }

    public function markUnderReview($adminUser): void
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $adminUser->id,
        ]);
    }
}
