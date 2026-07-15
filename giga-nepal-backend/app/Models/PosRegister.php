<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRegister extends Model
{
    use HasFactory;
    protected $fillable = ['warehouse_id','name','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function shifts(): HasMany { return $this->hasMany(PosShift::class); }
}
