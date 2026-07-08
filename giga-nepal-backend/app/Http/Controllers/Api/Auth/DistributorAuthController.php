<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DistributorRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\DistributorResource;
use App\Http\Resources\UserResource;
use App\Models\Distributor\Distributor;
use App\Services\Auth\AuthService;
use App\Services\Distributor\DistributorRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributorAuthController extends Controller
{
    use ApiResponses;

    public function register(DistributorRegisterRequest $request, DistributorRegistrationService $registration, AuthService $auth): JsonResponse
    {
        [$user, $distributor] = $registration->register($request->validated());

        return $this->success([
            'user' => new UserResource($user),
            'distributor' => new DistributorResource($distributor),
            'token' => $auth->issueToken($user),
            'onboarding_status' => 'submitted',
            'message' => 'Distributor account created with pending onboarding status.',
        ], 201);
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $user = $auth->validateCredentials($request->validated('email'), $request->validated('password'));
        if (! $user || ! $user->hasPermission('distributor.access')) {
            return $this->error('Invalid distributor credentials.', 422);
        }

        $distributor = Distributor::where('user_id', $user->id)->first();
        if (! $distributor) {
            return $this->error('Distributor profile is not available for this account.', 403);
        }
        if (in_array($distributor->status, ['suspended', 'rejected'], true)) {
            return $this->error('Distributor account is not active for login.', 403);
        }

        return $this->success([
            'user' => new UserResource($user),
            'distributor' => new DistributorResource($distributor),
            'token' => $auth->issueToken($user),
            'portal_status' => $distributor->status === 'approved' ? 'active' : 'early_access_pending',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $distributor = Distributor::where('user_id', $request->user()->id)->first();

        return $this->success([
            'user' => new UserResource($request->user()),
            'distributor' => $distributor ? new DistributorResource($distributor) : null,
        ]);
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->logout($request->user());

        return $this->success(['message' => 'Logged out.']);
    }
}
