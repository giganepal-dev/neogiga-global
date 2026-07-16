<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomColumnMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'column_name',
        'standard_field',
        'aliases',
        'is_required',
        'priority',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_required' => 'boolean',
    ];
}
