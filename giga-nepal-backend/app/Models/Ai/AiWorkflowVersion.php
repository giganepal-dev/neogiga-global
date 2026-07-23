<?php

namespace App\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWorkflowVersion extends Model
{
    protected $fillable = [
        'workflow_name',
        'version',
        'status',
        'config',
        'prompts',
        'parameters',
        'description',
        'created_by',
        'activated_at',
        'deprecated_at',
        'metadata',
    ];

    protected $casts = [
        'config' => 'array',
        'prompts' => 'array',
        'parameters' => 'array',
        'activated_at' => 'datetime',
        'deprecated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope by workflow name.
     */
    public function scopeOfWorkflow($query, string $name)
    {
        return $query->where('workflow_name', $name);
    }

    /**
     * Scope by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to active versions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the active version for a workflow.
     */
    public static function getActive(string $workflowName): ?self
    {
        return static::where('workflow_name', $workflowName)
            ->where('status', 'active')
            ->latest()
            ->first();
    }

    /**
     * Activate this version (deactivate others).
     */
    public function activate(): void
    {
        static::where('workflow_name', $this->workflow_name)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    /**
     * Deprecate this version.
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => 'deprecated',
            'deprecated_at' => now(),
        ]);
    }
}
