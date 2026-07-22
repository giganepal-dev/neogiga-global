<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailTag extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'color',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailTag $tag) {
            if (empty($tag->uuid)) {
                $tag->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(EmailSubscriber::class, 'email_subscriber_tags')
            ->withPivot('source', 'assigned_at', 'assigned_by')
            ->withTimestamps();
    }
}
