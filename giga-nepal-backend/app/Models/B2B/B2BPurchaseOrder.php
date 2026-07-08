<?php
namespace App\Models\B2B;
use Illuminate\Database\Eloquent\Model;
class B2BPurchaseOrder extends Model {
    protected $table='b2b_purchase_orders';
    protected $fillable=['b2b_account_id','b2b_quotation_id','po_number','status','currency_code','grand_total','price_snapshot','metadata'];
    protected $casts=['price_snapshot'=>'array','metadata'=>'array'];
}
