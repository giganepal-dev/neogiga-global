<?php

namespace App\Models\AiRobotics;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'user_id', 'name', 'email', 'phone', 'institution', 'status', 'metadata'];
    protected $casts = ['metadata' => 'array'];
    public function event() { return $this->belongsTo(Event::class, 'event_id'); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
