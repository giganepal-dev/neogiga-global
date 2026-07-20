<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerTerritoryRequest extends Model
{
    protected $fillable = [
        'reseller_id', 'marketplace_id', 'country_id',
        'document_company_reg', 'document_reseller_certificate', 'document_tax_certificate',
        'notes', 'status',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
