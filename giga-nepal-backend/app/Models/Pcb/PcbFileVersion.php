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
        'file_id', 'version_number', 'filename_original', 'filename_stored',
        'storage_path', 'file_size', 'change_summary', 'uploaded_by_id',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id ??= (string) Str::uuid());
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PcbFile::class, 'file_id');
    }
}
