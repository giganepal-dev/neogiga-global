<?php

namespace App\Models\Pcb;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbProject extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'marketplace',
        'name',
        'code',
        'description',
        'application_type',
        'confidentiality',
        'project_type',
        'target_quantity',
        'target_budget',
        'currency',
        'required_date',
        'destination_country',
        'shipping_postal_code',
        'preferred_region',
        'preferred_manufacturer_id',
        'preferred_warehouse_id',
        'assigned_engineer_id',
        'status',
        'current_version',
    ];

    protected $casts = [
        'target_quantity' => 'integer',
        'target_budget' => 'decimal:2',
        'required_date' => 'date',
        'current_version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->id)) {
                $project->id = (string) Str::uuid();
            }
            if (empty($project->code)) {
                $project->code = 'PCB-' . strtoupper(Str::random(8));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(PcbProjectMember::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PcbProjectVersion::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PcbFile::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PcbProjectVersion::class, 'current_version');
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function canUserAccess(User $user): bool
    {
        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Organization members with appropriate role can access
        if ($this->organization_id && $user->organization_id === $this->organization_id) {
            return $this->members()->where('user_id', $user->id)->exists();
        }

        // Check if user is explicitly added as member
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
