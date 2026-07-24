<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;

class RobotApplication extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function robotModels() { return $this->belongsToMany(RobotModel::class, 'robot_model_application'); }
}
