<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\ConsentManagementService;
use App\Services\Marketing\CustomerPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerProfileController extends Controller
{
    use ApiResponses;
    public function profile(Request $request): JsonResponse { return $this->success(DB::table('customer_profiles')->where('user_id', $request->user()?->id)->first()); }
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['first_name'=>'sometimes|string|max:120','last_name'=>'sometimes|string|max:120','phone'=>'sometimes|nullable|string|max:50','whatsapp_number'=>'sometimes|nullable|string|max:50','customer_type'=>'sometimes|string|max:40','preferred_language'=>'sometimes|string|max:12']);
        DB::table('customer_profiles')->updateOrInsert(['user_id'=>$request->user()?->id], $data + ['email'=>$request->user()?->email, 'updated_at'=>now(), 'created_at'=>now()]);
        return $this->success(['message'=>'Profile updated.']);
    }
    public function preferences(Request $request, CustomerPreferenceService $service): JsonResponse { $data=$request->validate(['category_interests'=>'array','brand_interests'=>'array','channels'=>'array','newsletter_categories'=>'array','analytics_opt_out'=>'boolean']); $profile=DB::table('customer_profiles')->where('user_id',$request->user()?->id)->first(); if(!$profile) return $this->error('Profile not found',404); $service->update($profile->id,$data); return $this->success(['message'=>'Preferences updated.']); }
    public function consent(Request $request, ConsentManagementService $service): JsonResponse { $data=$request->validate(['email'=>'nullable|email','phone'=>'nullable|string|max:60','channel'=>'required|string|max:40','purpose'=>'required|string|max:80','granted'=>'required|boolean']); $data['granted'] ? $service->grant(null,$data['email']??null,$data['phone']??null,$data['channel'],$data['purpose'],'api') : $service->revoke(null,$data['email']??null,$data['phone']??null,$data['channel'],'api'); return $this->success(['message'=>'Consent recorded.']); }
    public function unsubscribe(Request $request, ConsentManagementService $service): JsonResponse { $data=$request->validate(['email'=>'nullable|email','phone'=>'nullable|string|max:60','channel'=>'required|string|max:40','reason'=>'nullable|string|max:160']); $service->revoke(null,$data['email']??null,$data['phone']??null,$data['channel'],$data['reason']??'unsubscribe'); DB::table('unsubscribes')->insert(['email'=>$data['email']??null,'phone'=>$data['phone']??null,'channel'=>$data['channel'],'reason'=>$data['reason']??null,'unsubscribed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]); return $this->success(['message'=>'Unsubscribed.']); }
}
