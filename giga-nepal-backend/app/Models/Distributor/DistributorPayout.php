<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;

class DistributorPayout extends Model
{
    protected $fillable = [
        'distributor_id',
        'payout_number',
        'status',
        'currency_code',
        'gross_amount',
        'net_amount',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];
}
