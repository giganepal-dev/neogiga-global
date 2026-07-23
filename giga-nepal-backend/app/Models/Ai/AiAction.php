<?php

namespace App\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAction extends Model
{
    protected $fillable = [
        'user_id',
        'ai_session_id',
        'action_type',
        'action_category',
        'user_request',
        'model_interpretation',
        'proposed_action',
        'confirmation_required',
        'user_confirmation',
        'final_action',
        'result',
        'failure_reason',
        'status',
        'metadata',
    ];

    protected $casts = [
        'model_interpretation' => 'array',
        'proposed_action' => 'array',
        'final_action' => 'array',
        'result' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiSession(): BelongsTo
    {
        return $this->belongsTo(AiSession::class);
    }

    /**
     * Scope by action type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope by category.
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    /**
     * Scope by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to actions requiring confirmation.
     */
    public function scopeRequiringConfirmation($query)
    {
        return $query->where('confirmation_required', 'yes');
    }

    /**
     * Scope to pending confirmations.
     */
    public function scopePendingConfirmation($query)
    {
        return $query->where('confirmation_required', 'yes')
            ->where('user_confirmation', 'pending');
    }

    /**
     * Mark action as confirmed.
     */
    public function confirm(): void
    {
        $this->update([
            'user_confirmation' => 'confirmed',
            'status' => 'executing',
        ]);
    }

    /**
     * Mark action as rejected.
     */
    public function reject(?string $reason = null): void
    {
        $this->update([
            'user_confirmation' => 'rejected',
            'status' => 'cancelled',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark action as completed.
     */
    public function complete(array $result): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
        ]);
    }

    /**
     * Mark action as failed.
     */
    public function fail(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Check if action needs confirmation.
     */
    public function needsConfirmation(): bool
    {
        return $this->confirmation_required === 'yes' && $this->user_confirmation === 'pending';
    }

    /**
     * Check if action is high-impact.
     */
    public function isHighImpact(): bool
    {
        return in_array($this->action_type, [
            'add_to_cart',
            'submit_rfq',
            'send_quotation',
            'replace_bom_parts',
            'create_order',
            'send_seller_message',
            'export_sensitive_data',
        ]);
    }
}
