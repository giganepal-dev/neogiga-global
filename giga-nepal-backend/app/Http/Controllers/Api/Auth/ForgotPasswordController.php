<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use ApiResponses;

    public function sendResetLink(Request $request, AccountCommunicationService $communications): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        $email = mb_strtolower($request->string('email')->toString());
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $communications->passwordReset($user, $token, $url);
        }

        return $this->success(['message' => 'If an account exists for that email, a reset link has been queued.']);
    }
}
