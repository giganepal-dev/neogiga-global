<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorTerritoryRequest extends Model
{
    protected $fillable = [
        'distributor_id',
        'country_id',
        'region_id',
        'city_id',
        'territory_name',
        'document_company_reg',
        'document_distributor_agreement',
        'document_tax_certificate',
        'notes',
        'status',
        'rejection_reason',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }
}
