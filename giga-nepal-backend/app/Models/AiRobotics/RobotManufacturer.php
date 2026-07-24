<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RobotManufacturer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'description', 'country', 'regions_served',
        'certifications', 'website_url', 'contact_email', 'contact_phone',
        'is_robot_manufacturer', 'is_ai_hardware_manufacturer', 'is_software_provider',
        'is_active', 'seo_meta',
    ];

    protected $casts = [
        'regions_served' => 'array', 'certifications' => 'array', 'seo_meta' => 'array',
        'is_robot_manufacturer' => 'boolean', 'is_ai_hardware_manufacturer' => 'boolean',
        'is_software_provider' => 'boolean', 'is_active' => 'boolean',
    ];

    public function robotModels() { return $this->hasMany(RobotModel::class, 'manufacturer_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
