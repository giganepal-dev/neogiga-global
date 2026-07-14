<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Marketing\EmailQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthEmailOtpController extends Controller
{
    use ApiResponses;

    public function request(Request $r, EmailQueueService $queue): JsonResponse
    {
        $d = $r->validate(['email' => 'required|email:rfc|max:190']);
        $email = mb_strtolower($d['email']);
        $recent = DB::table('email_otps')->where('email', $email)->where('created_at', '>', now()->subSeconds((int) config('marketing.otp.resend_cooldown', 60)))->exists();
        if ($recent) {
            return $this->error('Please wait before requesting another OTP.', 429);
        } $otp = (string) random_int(100000, 999999);
        $otpId = DB::table('email_otps')->insertGetId(['email' => $email, 'otp_hash' => Hash::make($otp), 'purpose' => 'login', 'expires_at' => now()->addMinutes((int) config('marketing.otp.expiry_minutes', 10)), 'ip_address' => $r->ip(), 'created_at' => now(), 'updated_at' => now()]);
        $minutes = (int) config('marketing.otp.expiry_minutes', 10);
        $queue->queue($email, 'Your NeoGiga login OTP', 'Security code queued.', 'transactional', ['purpose' => 'email_otp', 'related_type' => 'email_otp', 'related_id' => $otpId, 'idempotency_key' => hash('sha256', "email-otp|{$otpId}|{$email}"), 'sensitive_html' => "Your NeoGiga login code is <strong>{$otp}</strong>. It expires in {$minutes} minutes. If you did not request it, ignore this message."]);

        return $this->success(['message' => 'OTP queued.']);
    }

    public function verify(Request $r): JsonResponse
    {
        $d = $r->validate(['email' => 'required|email:rfc', 'otp' => 'required|digits:6']);
        $email = mb_strtolower($d['email']);
        $row = DB::table('email_otps')->where('email', $email)->whereNull('used_at')->where('expires_at', '>', now())->latest('id')->first();
        if (! $row || $row->attempts >= 5 || ! Hash::check($d['otp'], $row->otp_hash)) {
            if ($row) {
                DB::table('email_otps')->where('id', $row->id)->increment('attempts');
            } DB::table('login_attempts')->insert(['email' => $email, 'method' => 'email_otp', 'successful' => false, 'ip_address' => $r->ip(), 'created_at' => now(), 'updated_at' => now()]);

            return $this->error('Invalid OTP.', 422);
        } DB::table('email_otps')->where('id', $row->id)->update(['used_at' => now(), 'updated_at' => now()]);
        $role = Role::firstOrCreate(['name' => 'customer'], ['display_name' => 'Customer', 'permissions' => ['cart.manage', 'checkout.create', 'orders.view'], 'is_active' => true]);
        $user = User::firstOrCreate(['email' => $email], ['name' => Str::before($email, '@'), 'password' => Str::random(32), 'role_id' => $role->id]);
        $token = Str::random(64);
        $user->forceFill(['api_token_hash' => hash('sha256', $token), 'email_verified_at' => $user->email_verified_at ?: now(), 'last_login_at' => now()])->save();
        DB::table('login_attempts')->insert(['email' => $email, 'user_id' => $user->id, 'method' => 'email_otp', 'successful' => true, 'ip_address' => $r->ip(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('customer_profiles')->updateOrInsert(['user_id' => $user->id], ['email' => $user->email, 'first_name' => $user->name, 'email_verified_at' => now(), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]);

        return $this->success(['user' => $user->only(['id', 'name', 'email']), 'token' => $token]);
    }
}
