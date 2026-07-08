<?php
namespace App\Http\Requests\Admin\B2B;
use Illuminate\Foundation\Http\FormRequest;
class AdminB2BQuotationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['b2b_account_id'=>['sometimes','nullable','integer','exists:b2b_accounts,id'],'b2b_quote_request_id'=>['sometimes','nullable','integer','exists:b2b_quote_requests,id'],'currency_code'=>['sometimes','string','size:3'],'shipping_total'=>['sometimes','numeric','min:0'],'valid_until'=>['required','date','after_or_equal:today'],'items'=>['required','array','min:1'],'items.*.product_id'=>['sometimes','nullable','integer','exists:products,id'],'items.*.sku'=>['sometimes','nullable','string','max:120'],'items.*.name'=>['required','string','max:190'],'items.*.quantity'=>['required','numeric','min:0.001'],'items.*.unit_price'=>['required','numeric','min:0'],'items.*.tax_amount'=>['sometimes','numeric','min:0']]; }
}
