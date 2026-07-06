<?php

namespace App\Models\Vendor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends BaseModel
{
    protected $table = 'vendor_documents';

    protected $fillable = [
        'vendor_id', 'document_type', 'document_name', 'file_path',
        'file_mime', 'file_size', 'expiry_date', 'is_verified',
        'verified_at', 'verified_by', 'rejection_reason', 'metadata'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function verifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }
}
