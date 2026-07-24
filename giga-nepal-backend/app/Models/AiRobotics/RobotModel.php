<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RobotModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'model_number', 'manufacturer_id', 'robot_type_id',
        'description', 'short_description', 'image', 'images', 'videos',
        'payload_kg', 'reach_mm', 'degrees_of_freedom',
        'length_mm', 'width_mm', 'height_mm', 'weight_kg', 'speed_mps',
        'battery_type', 'battery_runtime_min', 'charging_time_min',
        'sensors', 'camera_system', 'lidar', 'radar',
        'compute_platform', 'ai_accelerator', 'operating_system',
        'ros_support', 'ros2_support', 'programming_languages',
        'sdk_available', 'api_available', 'simulation_support', 'digital_twin_support',
        'indoor_outdoor', 'ip_rating', 'certifications', 'safety_features',
        'documentation_url', 'datasheet_url', 'cad_files_url', 'software_download_url',
        'global_price', 'currency', 'is_active', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'images' => 'array', 'videos' => 'array', 'sensors' => 'array',
        'programming_languages' => 'array', 'certifications' => 'array',
        'safety_features' => 'array', 'seo_meta' => 'array',
        'ros_support' => 'boolean', 'ros2_support' => 'boolean',
        'sdk_available' => 'boolean', 'api_available' => 'boolean',
        'simulation_support' => 'boolean', 'digital_twin_support' => 'boolean',
        'is_active' => 'boolean', 'is_featured' => 'boolean',
        'payload_kg' => 'decimal:2', 'reach_mm' => 'decimal:2',
        'length_mm' => 'decimal:2', 'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2', 'weight_kg' => 'decimal:2',
        'speed_mps' => 'decimal:2', 'global_price' => 'decimal:2',
    ];

    public function manufacturer(): BelongsTo { return $this->belongsTo(RobotManufacturer::class, 'manufacturer_id'); }
    public function type(): BelongsTo { return $this->belongsTo(RobotType::class, 'robot_type_id'); }
    public function applications(): BelongsToMany { return $this->belongsToMany(RobotApplication::class, 'robot_model_application'); }
    public function categories(): BelongsToMany { return $this->belongsToMany(\App\Models\Marketplace\ProductCategory::class, 'robot_model_category', 'robot_model_id', 'category_id'); }
    public function compatibleProducts(): BelongsToMany { return $this->belongsToMany(\App\Models\Marketplace\Product::class, 'robot_compatible_products'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
}
