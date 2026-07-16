<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_request_id',
        'version_number',
        'snapshot_data',
        'change_summary',
        'user_id',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
    ];

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
