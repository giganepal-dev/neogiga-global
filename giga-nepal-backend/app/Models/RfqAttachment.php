<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RfqAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfq_request_id',
        'rfq_item_id',
        'user_id',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'download_hash',
        'attachment_type',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'last_downloaded_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($attachment) {
            if (empty($attachment->download_hash)) {
                $attachment->download_hash = Str::random(32);
            }
        });
    }

    public function rfqRequest(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class);
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('rfq.attachments.download', $this->download_hash);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }
}
