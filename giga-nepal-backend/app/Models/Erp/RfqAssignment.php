<?php

namespace App\Models\Erp;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqAssignment extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'invited_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
