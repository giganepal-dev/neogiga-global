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
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Partner\PartnerCountryService;

class DistributorAuthController extends Controller
{
    use ApiResponses;

    public function register(DistributorRegisterRequest $request, DistributorRegistrationService $registration, AuthService $auth, AccountCommunicationService $communications, PartnerCountryService $countries): JsonResponse
    {
        $data = $request->validated();
        $data['country_id'] = $countries->resolveSignupCountry($request, $data['country_id'] ?? null);
        $data['operating_scope'] = $countries->normalizeScope($data['operating_scope'] ?? null);
        [$user, $distributor] = $registration->register($data);
        $communications->application($user, 'distributor', (int) $distributor->id);

        return $this->success([
            'user' => new UserResource($user),
            'distributor' => new DistributorResource($distributor),
            'token' => $user->createToken('auth-token')->plainTextToken,
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

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'user' => new UserResource($user),
            'distributor' => new DistributorResource($distributor),
            'token' => $user->createToken('auth-token')->plainTextToken,
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
