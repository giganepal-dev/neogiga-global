<?php
namespace App\Models\Bom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BomProject extends Model {
    protected $fillable=['bom_project_category_id','marketplace_id','country_id','title','slug','difficulty','estimated_build_time','description','safety_notes','required_tools','is_public','status','seo_meta','metadata'];
    protected $casts=['required_tools'=>'array','is_public'=>'boolean','seo_meta'=>'array','metadata'=>'array'];
    public function items(): HasMany { return $this->hasMany(BomProjectItem::class); }
}
