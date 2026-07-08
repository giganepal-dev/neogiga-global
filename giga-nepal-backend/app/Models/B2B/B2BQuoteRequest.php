<?php
namespace App\Models\B2B;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class B2BQuoteRequest extends Model {
    protected $table='b2b_quote_requests';
    protected $fillable=['b2b_account_id','rfq_number','status','contact_name','contact_email','currency_code','notes','metadata'];
    protected $casts=['metadata'=>'array'];
    public function items(): HasMany { return $this->hasMany(B2BQuoteItem::class); }
}
