<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class EmailTemplate extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'category',
        'subject',
        'preview_text',
        'html_content',
        'text_content',
        'merge_tags',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'merge_tags' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailTemplate $template) {
            if (empty($template->uuid)) {
                $template->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
