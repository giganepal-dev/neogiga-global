<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosShift extends Model
{
    use HasFactory;
    protected $fillable = ['register_id','user_id','opening_cash','closing_cash','expected_cash','status','notes','started_at','ended_at'];
    protected $casts = ['opening_cash'=>'decimal:2','closing_cash'=>'decimal:2','expected_cash'=>'decimal:2','started_at'=>'datetime','ended_at'=>'datetime'];
    public function register(): BelongsTo { return $this->belongsTo(PosRegister::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
