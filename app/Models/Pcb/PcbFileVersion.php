<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbFileVersion extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'file_id',
        'version_number',
        'uploaded_by_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'checksum_sha256',
        'change_summary',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            if (empty($version->id)) {
                $version->id = (string) Str::uuid();
            }
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
