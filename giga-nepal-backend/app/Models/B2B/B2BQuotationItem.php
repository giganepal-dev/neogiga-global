<?php
namespace App\Models\B2B;
use Illuminate\Database\Eloquent\Model;
class B2BQuotationItem extends Model {
    protected $table='b2b_quotation_items';
    protected $fillable=['b2b_quotation_id','product_id','sku','name','quantity','unit_price','tax_amount','line_total'];
}
