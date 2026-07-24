<?php

namespace App\Models\Lms;

use Illuminate\Database\Eloquent\Model;

abstract class LmsModel extends Model
{
    /**
     * Protect primary key and timestamps from mass assignment.
     * Child models should define $fillable for their specific fields.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

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
