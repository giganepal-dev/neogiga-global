<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFulfillment extends Model
{
    use HasFactory;
    protected $fillable = ['order_id','seller_id','warehouse_id','status','tracking_number','carrier','items','subtotal','shipping_cost','tax_amount','shipped_at','delivered_at'];
    protected $casts = ['items'=>'array','subtotal'=>'decimal:2','shipping_cost'=>'decimal:2','tax_amount'=>'decimal:2','shipped_at'=>'datetime','delivered_at'=>'datetime'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
}
