<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_quote_id',
        'version_number',
        'snapshot_data',
        'change_summary',
        'user_id',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
    ];

    public function customerQuote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
