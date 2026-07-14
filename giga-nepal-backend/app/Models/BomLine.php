<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BomLine extends Model
{
    use HasFactory;
    protected $fillable = ['bom_project_id','reference_designator','quantity','manufacturer_part_number','supplier_sku','description','manufacturer','match_status','matched_product_id','matched_seller_offer_id','alternatives','errors'];
    protected $casts = ['alternatives'=>'array','errors'=>'array'];
    public function project(): BelongsTo { return $this->belongsTo(BomProject::class, 'bom_project_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'matched_product_id'); }
    public function sellerOffer(): BelongsTo { return $this->belongsTo(SellerOffer::class, 'matched_seller_offer_id'); }
}
