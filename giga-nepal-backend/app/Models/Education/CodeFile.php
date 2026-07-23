<?php

namespace App\Models\Education;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeFile extends Model
{
    protected $fillable = [
        'education_project_id', 'title', 'target_board', 'language', 'version',
        'dependencies', 'libraries', 'file_tree', 'source_code', 'build_instructions',
        'upload_instructions', 'expected_serial_output', 'configuration_values',
        'license', 'author_id', 'verification_status', 'last_tested_at', 'download_count',
    ];

    protected $casts = [
        'dependencies' => 'array', 'libraries' => 'array', 'file_tree' => 'array',
        'configuration_values' => 'array', 'last_tested_at' => 'datetime',
        'download_count' => 'integer',
    ];

    public function project(): BelongsTo { return $this->belongsTo(EducationProject::class, 'education_project_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeVerified($q) { return $q->where('verification_status', 'verified'); }
    public function scopeOfLanguage($q, string $lang) { return $q->where('language', $lang); }
    public function incrementDownload(): void { $this->increment('download_count'); }
}
