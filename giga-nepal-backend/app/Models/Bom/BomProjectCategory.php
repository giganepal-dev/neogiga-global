<?php
namespace App\Models\Bom;
use Illuminate\Database\Eloquent\Model;
class BomProjectCategory extends Model {
    protected $fillable=['name','slug','is_active'];
    protected $casts=['is_active'=>'boolean'];
}
