<?php

namespace App\Models\AiPlatform;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiProjectTemplate extends Model
{
    use HasUuids;
    use SoftDeletes;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'category',
        'difficulty_level',
        'estimated_build_time',
        'required_components',
        'optional_components',
        'required_tools',
        'battery_power_requirements',
        'wiring_overview',
        'lms_lesson_links',
        'sample_code_placeholders',
        'product_matching_placeholders',
        'safety_notes',
        'status',
        'organization_id',
        'marketplace_id',
        'country_id',
        'permission_scope',
        'source_type',
        'source_id',
        'source_provenance',
        'audit_metadata',
    ];

    protected $casts = [
        'required_components' => 'array',
        'optional_components' => 'array',
        'required_tools' => 'array',
        'battery_power_requirements' => 'array',
        'lms_lesson_links' => 'array',
        'sample_code_placeholders' => 'array',
        'product_matching_placeholders' => 'array',
        'safety_notes' => 'array',
        'source_provenance' => 'array',
        'audit_metadata' => 'array',
    ];
}
