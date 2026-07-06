<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Model;

abstract class LmsModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'seo_meta' => 'array',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'issued_at' => 'datetime',
        'occurred_at' => 'datetime',
    ];
}
