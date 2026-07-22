<?php

namespace App\Models\EmailMarketing;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateVersion extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'template_id',
        'version_number',
        'html_content',
        'text_content',
        'changes_summary',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}
