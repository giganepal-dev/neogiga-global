<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTag extends Model
{
    protected $table = 'email_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function subscribers(): HasMany
    {
        return $this->hasMany(EmailSubscriber::class);
    }
}
