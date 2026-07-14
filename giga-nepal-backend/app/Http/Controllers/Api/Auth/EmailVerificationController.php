<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponses;

    public function sendVerification(Request $request, AccountCommunicationService $communications): JsonResponse
    {
        if ($request->user()->email_verified_at) {
            return $this->success(['message' => 'Email already verified.']);
        }

        $communications->verification($request->user());

        return $this->success(['message' => 'Verification link queued through the transactional channel.']);
    }

    public function verify(Request $request, int $id, string $hash, AccountCommunicationService $communications): JsonResponse
    {
        $user = User::find($id);
        if (! $user || ! hash_equals(sha1(mb_strtolower($user->email)), $hash)) {
            return $this->error('Invalid verification link.', 403);
        }
        if ($user->email_verified_at) {
            return $this->success(['message' => 'Email already verified.']);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $communications->verified($user->fresh());

        return $this->success(['message' => 'Email verified successfully.']);
    }
}
