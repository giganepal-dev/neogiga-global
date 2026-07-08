<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;

class DistributorProfile extends Model
{
    protected $fillable = ['distributor_id', 'business_name', 'tax_number', 'registration_number', 'address', 'documents', 'capabilities'];

    protected $casts = ['documents' => 'array', 'capabilities' => 'array'];
}
