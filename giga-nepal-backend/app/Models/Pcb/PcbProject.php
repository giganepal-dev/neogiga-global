<?php

namespace App\Models\Pcb;

use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
                $model->code = 'PCB-'.strtoupper(Str::random(6));
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
        return $this->belongsTo(Organization::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'assigned_engineer_id');
    }

    public function preferredManufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'preferred_manufacturer_id');
    }

    public function preferredWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'preferred_warehouse_id');
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
            ->where('version_number', $this->current_version);
    }

    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visible) use ($user): void {
            $visible->where('user_id', $user->id)
                ->orWhereHas('members', fn (Builder $members) => $members
                    ->where('user_id', $user->id)
                    ->where(fn (Builder $expiry) => $expiry
                        ->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now())));

            if ($user->organization_id ?? null) {
                $visible->orWhere('organization_id', $user->organization_id);
            }
        });
    }

    public function canBeAccessedBy($user): bool
    {
        if (! $user) {
            return false;
        }

        // Owner can always access
        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        // Check organization membership
        if ($this->organization_id && (int) ($user->organization_id ?? 0) === (int) $this->organization_id) {
            return true;
        }

        // Check explicit project membership
        return $this->members()
            ->where('user_id', $user->id)
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>', now()))
            ->exists();
    }

    public function canBeEditedBy($user): bool
    {
        if (! $user) {
            return false;
        }

        // Owner can always edit
        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        // Check explicit project membership with editor/admin/owner role
        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor'])
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>', now()))
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
