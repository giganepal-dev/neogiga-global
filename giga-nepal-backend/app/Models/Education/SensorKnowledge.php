<?php

namespace App\Models\Education;

use Illuminate\Database\Eloquent\Model;

class SensorKnowledge extends Model
{
    protected $fillable = [
        'sensor_type', 'display_name', 'function_description', 'measurement_principle',
        'input_output_type', 'voltage_range', 'current_consumption', 'interface',
        'range', 'accuracy', 'resolution', 'response_time', 'operating_conditions',
        'calibration_notes', 'compatible_controllers', 'compatible_libraries',
        'wiring_examples', 'code_examples', 'applications', 'limitations',
        'safety_notes', 'alternative_product_ids', 'premium_product_ids', 'budget_product_ids', 'metadata',
    ];

    protected $casts = [
        'compatible_controllers' => 'array', 'compatible_libraries' => 'array',
        'applications' => 'array', 'limitations' => 'array',
        'alternative_product_ids' => 'array', 'premium_product_ids' => 'array',
        'budget_product_ids' => 'array', 'metadata' => 'array',
    ];

    public function scopeOfType($q, string $type) { return $q->where('sensor_type', $type); }
}
