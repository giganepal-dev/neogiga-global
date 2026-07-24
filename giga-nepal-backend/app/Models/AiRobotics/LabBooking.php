<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;

class LabBooking extends Model
{
    protected $fillable = [
        'user_id', 'booking_type', 'status', 'preferred_date', 'preferred_time',
        'institution_name', 'contact_name', 'contact_email', 'contact_phone',
        'requirements', 'metadata', 'confirmed_at', 'completed_at',
    ];

    protected $casts = [
        'preferred_date' => 'date', 'preferred_time' => 'datetime',
        'metadata' => 'array', 'confirmed_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
