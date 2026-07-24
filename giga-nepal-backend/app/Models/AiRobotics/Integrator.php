<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integrator extends Model
{
    use SoftDeletes;

    protected $table = 'ai_robotics_integrators';

    protected $fillable = [
        'name', 'slug', 'logo', 'description', 'country',
        'regions_served', 'services', 'certifications',
        'website_url', 'contact_email', 'contact_phone',
        'is_active', 'seo_meta',
    ];

    protected $casts = [
        'regions_served' => 'array', 'services' => 'array',
        'certifications' => 'array', 'seo_meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
}
