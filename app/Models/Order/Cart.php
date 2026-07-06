<?php
namespace App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model {
    protected $fillable = ['user_id', 'marketplace_id', 'session_id', 'status', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function marketplace() { return $this->belongsTo(\App\Models\Marketplace\Marketplace::class); }
    public function items() { return $this->hasMany(CartItem::class); }
}
