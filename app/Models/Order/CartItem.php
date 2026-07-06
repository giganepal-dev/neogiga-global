<?php
namespace App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model {
    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'quantity', 'price', 'vendor_id'];
    protected $casts = ['price' => 'decimal:2'];
    public function cart() { return $this->belongsTo(Cart::class); }
    public function product() { return $this->belongsTo(\App\Models\Product\Product::class); }
    public function variant() { return $this->belongsTo(\App\Models\Product\ProductVariant::class); }
    public function vendor() { return $this->belongsTo(\App\Models\Vendor\Vendor::class); }
}
