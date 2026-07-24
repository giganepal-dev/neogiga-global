<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderInstalment extends Model
{
    protected $table = 'pos_order_instalments';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = ['due_date' => 'date', 'paid_at' => 'datetime'];

    public function order() { return $this->belongsTo(Order::class); }
}
