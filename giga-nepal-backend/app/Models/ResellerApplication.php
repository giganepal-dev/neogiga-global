<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerApplication extends Model
{
    protected $fillable = [
        'user_id', 'marketplace_id', 'company_name', 'contact_person', 'email', 'phone',
        'country_id', 'registration_number', 'tax_number',
        'document_company_reg', 'document_reseller_certificate', 'document_tax_certificate', 'document_gst_info',
        'territory_notes', 'status', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
