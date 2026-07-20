<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosCustomerAccount extends Model
{
    protected $table = 'pos_customer_accounts';

    protected $fillable = [
        'marketplace_id',
        'account_number',
        'name',
        'email',
        'phone',
        'store_credit_balance',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
