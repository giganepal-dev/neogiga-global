<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_number', 'category', 'supplier_id', 'marketplace_id', 'amount',
        'tax_amount', 'currency', 'status', 'expense_date', 'description', 'created_by', 'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'expense_date' => 'date',
        'meta' => 'array',
    ];
}
