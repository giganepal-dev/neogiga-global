<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    protected $fillable = [
        'code', 'name', 'is_enabled', 'is_live', 'supported_currencies', 'config', 'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_live' => 'boolean',
        'supported_currencies' => 'array',
        'config' => 'array',
    ];

    // config holds PUBLIC settings only; secrets/keys live in .env (never here).
}
