<?php
namespace App\Models\B2B;
use Illuminate\Database\Eloquent\Model;
class B2BQuoteItem extends Model {
    protected $table='b2b_quote_items';
    protected $fillable=['b2b_quote_request_id','product_id','sku','name','quantity','target_price','notes'];
}
