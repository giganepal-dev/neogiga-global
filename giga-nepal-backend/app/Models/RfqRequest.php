<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class RfqRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'status',
        'contact_name',
        'company_name',
        'email',
        'phone',
        'whatsapp',
        'country_id',
        'state_province',
        'city',
        'billing_address',
        'shipping_address',
        'tax_vat_number',
        'company_registration_number',
        'industry',
        'project_name',
        'project_description',
        'preferred_contact_method',
        'required_response_date',
        'comments',
        'assigned_salesperson_id',
        'assigned_sourcing_agent_id',
        'assigned_product_specialist_id',
        'submitted_at',
        'quoted_at',
        'expires_at',
        'allow_alternatives',
        'currency',
        'version',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'allow_alternatives' => 'boolean',
        'submitted_at' => 'datetime',
        'quoted_at' => 'datetime',
        'expires_at' => 'datetime',
        'required_response_date' => 'date',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($rfq) {
            if (empty($rfq->public_id)) {
                $rfq->public_id = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function assignedSalesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_salesperson_id');
    }

    public function assignedSourcingAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sourcing_agent_id');
    }

    public function assignedProductSpecialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_product_specialist_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RfqVersion::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RfqStatusHistory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RfqAssignment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RfqMessage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RfqAttachment::class);
    }

    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(SupplierQuote::class);
    }

    public function customerQuotes(): HasMany
    {
        return $this->hasMany(CustomerQuote::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(QuoteActivityLog::class, 'activitable');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review', 'product_matching', 'supplier_inquiry', 'partially_quoted', 'quoted']);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision_requested']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }
}
