<?php
namespace App\Http\Requests\B2B;
use Illuminate\Foundation\Http\FormRequest;
class B2BRfqRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['contact_name'=>['sometimes','nullable','string','max:190'],'contact_email'=>['sometimes','nullable','email:rfc','max:190'],'currency_code'=>['sometimes','string','size:3'],'notes'=>['sometimes','nullable','string','max:3000'],'items'=>['required','array','min:1'],'items.*.product_id'=>['sometimes','nullable','integer','exists:products,id'],'items.*.sku'=>['sometimes','nullable','string','max:120'],'items.*.name'=>['required','string','max:190'],'items.*.quantity'=>['required','numeric','min:0.001'],'items.*.target_price'=>['sometimes','nullable','numeric','min:0'],'items.*.notes'=>['sometimes','nullable','string','max:1000']]; }
}
