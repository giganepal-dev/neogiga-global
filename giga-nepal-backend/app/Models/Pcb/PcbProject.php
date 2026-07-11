<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PcbProject extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->code)) {
                $model->code = 'PCB-' . strtoupper(Str::random(6));
            }
        });
    }

    protected $fillable = [
        'user_id', 'organization_id', 'marketplace',
        'name', 'code', 'description', 'application_type',
        'confidentiality', 'project_type',
        'target_quantity', 'target_budget', 'currency', 'required_date',
        'destination_country', 'shipping_postal_code', 'preferred_region',
        'preferred_manufacturer_id', 'preferred_warehouse_id',
        'assigned_engineer_id',
        'status', 'current_version',
    ];

    protected $casts = [
        'target_quantity' => 'integer',
        'target_budget' => 'decimal:2',
        'required_date' => 'date',
        'current_version' => 'integer',
        'nda_accepted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'assigned_engineer_id');
    }

    public function preferredManufacturer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Manufacturer::class, 'preferred_manufacturer_id');
    }

    public function preferredWarehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Warehouse::class, 'preferred_warehouse_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PcbProjectMember::class, 'project_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PcbProjectVersion::class, 'project_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PcbFile::class, 'project_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(PcbProjectActivityLog::class, 'project_id');
    }

    public function gerberAnalysisRuns(): HasMany
    {
        return $this->hasMany(PcbGerberAnalysisRun::class, 'project_id');
    }

    public function quoteConfigurations(): HasMany
    {
        return $this->hasMany(PcbQuoteConfiguration::class, 'project_id');
    }

    public function cplImports(): HasMany
    {
        return $this->hasMany(PcbCplImport::class, 'project_id');
    }

    public function componentMatches(): HasMany
    {
        return $this->hasMany(PcbComponentMatch::class, 'project_id');
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(PcbProjectVersion::class, 'project_id')
            ->whereColumn('pcb_project_versions.version_number', 'pcb_projects.current_version');
    }

    public function canBeAccessedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Organization access is intentionally scoped to the same organization.
        if ($this->organization_id && (int) ($user->organization_id ?? 0) === (int) $this->organization_id) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        if (! $member || $member->hasExpired()) {
            return false;
        }

        return $this->confidentiality !== 'nda_required' || $member->nda_accepted;
    }

    public function canBeEditedBy($user): bool
    {
        if (! $this->canBeAccessedBy($user)) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor', 'engineer'])
            ->where(fn ($query) => $query->whereNull('access_expires_at')->orWhere('access_expires_at', '>', now()))
            ->exists();
    }

    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'draft' => 'gray',
            'requirements_pending' => 'yellow',
            'design_requested' => 'blue',
            'design_in_progress' => 'blue',
            'design_review' => 'purple',
            'design_approved' => 'green',
            'files_ready' => 'green',
            'quote_pending' => 'yellow',
            'quoted' => 'green',
            'awaiting_approval' => 'orange',
            'ordered' => 'blue',
            'manufacturing' => 'blue',
            'inspection' => 'purple',
            'shipped' => 'green',
            'completed' => 'green',
            'on_hold' => 'red',
            'cancelled' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
