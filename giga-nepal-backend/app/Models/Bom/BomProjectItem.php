<?php
namespace App\Models\Bom;
use Illuminate\Database\Eloquent\Model;
class BomProjectItem extends Model {
    protected $fillable=['bom_project_id','product_id','category_id','name','required_or_optional','quantity','reason','substitute_allowed','priority','notes'];
    protected $casts=['substitute_allowed'=>'boolean'];
}
