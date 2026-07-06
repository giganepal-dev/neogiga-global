<?php

namespace App\Models\Lms;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsProjectComponent extends BaseModel
{
    protected $table = 'lms_project_components';

    protected $fillable = [
        'lms_project_id',
        'component_name',
        'description',
        'quantity',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_required' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LmsProject::class, 'lms_project_id');
    }
}
