<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends LmsModel
{
    protected $table = 'lms_projects';

    public function components(): HasMany
    {
        return $this->hasMany(ProjectComponent::class, 'lms_project_id')->orderBy('sort_order');
    }

    public function codeSamples(): HasMany
    {
        return $this->hasMany(CodeSample::class, 'lms_project_id')->orderBy('sort_order');
    }
}
