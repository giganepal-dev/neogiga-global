<?php
namespace App\Http\Requests\B2B;
use Illuminate\Foundation\Http\FormRequest;
class B2BApplyRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name'=>['required','string','min:2','max:190'],'type'=>['required','string','max:80'],'email'=>['sometimes','nullable','email:rfc','max:190'],'phone'=>['sometimes','nullable','string','max:40'],'pan_vat_number'=>['sometimes','nullable','string','max:80'],'country_id'=>['sometimes','nullable','integer','exists:countries,id'],'marketplace_id'=>['sometimes','nullable','integer','exists:marketplaces,id']]; }
}
