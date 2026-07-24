<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = [
        'user_id', 'robot_model_id', 'manufacturer_id', 'status',
        'institution_name', 'contact_name', 'contact_email', 'contact_phone',
        'requirements', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function robotModel() { return $this->belongsTo(RobotModel::class); }
    public function manufacturer() { return $this->belongsTo(RobotManufacturer::class, 'manufacturer_id'); }
}
