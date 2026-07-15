<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbOrder extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->order_number)) {
                $model->order_number = 'PCB-ORD-'.strtoupper(Str::random(8));
            }
        });
    }

    protected $fillable = [
        'project_id', 'quote_id', 'user_id', 'order_number',
        'status', 'payment_status', 'currency', 'total_amount', 'customer_notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PcbProject::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PcbQuoteConfiguration::class, 'quote_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }
}
