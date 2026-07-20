<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2BAccount extends Model
{
    protected $table = 'b2b_accounts';

    protected $fillable = ['name', 'slug', 'type', 'status', 'email', 'phone', 'pan_vat_number', 'document_company_reg', 'document_tax_certificate', 'document_institutional_id', 'country_id', 'marketplace_id', 'account_manager_id', 'distributor_id', 'credit_limit', 'billing_address', 'shipping_address', 'metadata'];

    protected $casts = ['billing_address' => 'array', 'shipping_address' => 'array', 'metadata' => 'array'];

    public function users(): HasMany
    {
        return $this->hasMany(B2BAccountUser::class);
    }
}
