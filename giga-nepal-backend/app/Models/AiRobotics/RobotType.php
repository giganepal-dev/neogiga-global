<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;

class RobotType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function robotModels() { return $this->hasMany(RobotModel::class); }
}
