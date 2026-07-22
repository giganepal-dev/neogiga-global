<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailImportMapping extends Model
{
    use HasFactory;

    protected $table = 'email_import_mappings';

    protected $fillable = [
        'name',
        'description',
        'column_mappings',
        'default_subscriber_type',
        'default_source',
        'default_country_code',
        'is_global',
        'created_by',
    ];

    protected $casts = [
        'column_mappings' => 'array',
        'is_global' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeUser($query, int $userId)
    {
        return $query->where('created_by', $userId)->orWhere('is_global', true);
    }

    public function getMappingForColumn(string $column): ?string
    {
        $mappings = $this->column_mappings ?? [];
        return $mappings[$column] ?? null;
    }
}
