<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsSkillLevel extends BaseModel
{
    protected $table = 'lms_skill_levels';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(LmsCourse::class);
    }
}
