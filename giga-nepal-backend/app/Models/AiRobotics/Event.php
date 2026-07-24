<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $table = 'ai_robotics_events';

    protected $fillable = [
        'name', 'slug', 'event_type', 'description', 'image',
        'location', 'location_type', 'starts_at', 'ends_at',
        'registration_url', 'ticket_price', 'currency',
        'max_attendees', 'current_attendees',
        'is_active', 'is_featured', 'seo_meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
        'ticket_price' => 'decimal:2', 'seo_meta' => 'array',
        'is_active' => 'boolean', 'is_featured' => 'boolean',
    ];

    public function registrations() { return $this->hasMany(EventRegistration::class, 'event_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeUpcoming($q) { return $q->where('starts_at', '>=', now())->active(); }
}
