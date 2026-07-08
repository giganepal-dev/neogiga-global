<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;

class DistributorCommission extends Model
{
    protected $fillable = ['distributor_id', 'distributor_order_id', 'status', 'currency_code', 'base_amount', 'commission_amount', 'approved_at', 'paid_at', 'metadata'];

    protected $casts = ['approved_at' => 'datetime', 'paid_at' => 'datetime', 'metadata' => 'array'];
}
