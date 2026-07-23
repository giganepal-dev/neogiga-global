<?php

namespace App\Models\Education;

use App\Models\Lms\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'category', 'subcategory',
        'skill_level', 'age_group', 'grade_level', 'estimated_duration_minutes',
        'estimated_cost', 'currency', 'learning_outcomes', 'required_prior_knowledge',
        'main_controller', 'supported_controllers', 'sensor_types', 'actuator_types',
        'communication_methods', 'power_requirements', 'tools_required', 'safety_warnings',
        'wiring_instructions', 'pin_mapping', 'assembly_steps', 'testing_procedure',
        'calibration_procedure', 'troubleshooting', 'expected_output',
        'project_images', 'diagrams', 'videos', 'datasheets', 'downloads',
        'lms_course_id', 'lms_quiz_id', 'lms_certificate_id',
        'source_references', 'author_id', 'reviewer_id',
        'verification_status', 'last_reviewed_at', 'supported_marketplaces',
        'is_featured', 'view_count', 'enrollment_count', 'rating_avg', 'rating_count',
        'metadata',
    ];

    protected $casts = [
        'supported_controllers' => 'array', 'sensor_types' => 'array',
        'actuator_types' => 'array', 'communication_methods' => 'array',
        'pin_mapping' => 'array', 'project_images' => 'array',
        'diagrams' => 'array', 'videos' => 'array', 'datasheets' => 'array',
        'downloads' => 'array', 'source_references' => 'array',
        'supported_marketplaces' => 'array', 'metadata' => 'array',
        'estimated_cost' => 'decimal:2', 'rating_avg' => 'decimal:2',
        'is_featured' => 'boolean', 'estimated_duration_minutes' => 'integer',
        'view_count' => 'integer', 'enrollment_count' => 'integer',
        'rating_count' => 'integer', 'last_reviewed_at' => 'datetime',
    ];

    public function bomLines(): HasMany { return $this->hasMany(BomLine::class); }
    public function codeFiles(): HasMany { return $this->hasMany(CodeFile::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class, 'lms_course_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }

    public function scopePublished($q) { return $q->where('verification_status', 'published'); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
    public function scopeOfCategory($q, string $cat) { return $q->where('category', $cat); }
    public function scopeOfSkillLevel($q, string $level) { return $q->where('skill_level', $level); }
    public function scopeOfController($q, string $controller) { return $q->where('main_controller', $controller); }

    public function getDifficultyLabelAttribute(): string {
        return match($this->skill_level) {
            'beginner' => 'Beginner', 'intermediate' => 'Intermediate',
            'advanced' => 'Advanced', 'expert' => 'Expert',
            default => ucfirst($this->skill_level),
        };
    }

    public function getDurationLabelAttribute(): string {
        $mins = $this->estimated_duration_minutes;
        if (!$mins) return 'Variable';
        if ($mins < 60) return "{$mins} min";
        $hours = floor($mins / 60);
        $remain = $mins % 60;
        return $remain > 0 ? "{$hours}h {$remain}m" : "{$hours}h";
    }

    public function incrementView(): void { $this->increment('view_count'); }
}
