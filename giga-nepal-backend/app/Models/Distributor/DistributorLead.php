<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;

class DistributorLead extends Model
{
    protected $fillable = ['distributor_id', 'name', 'email', 'phone', 'company', 'status', 'estimated_value', 'notes', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
