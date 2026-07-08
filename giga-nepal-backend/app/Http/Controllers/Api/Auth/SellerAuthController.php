<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SellerRegisterRequest;
use App\Http\Resources\SellerResource;
use App\Http\Resources\UserResource;
use App\Models\Marketplace\Vendor;
use App\Services\Auth\AuthService;
use App\Services\Vendor\SellerRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerAuthController extends Controller
{
    use ApiResponses;

    public function register(SellerRegisterRequest $request, SellerRegistrationService $registration, AuthService $auth): JsonResponse
    {
        [$user, $vendor] = $registration->register($request->validated());

        return $this->success([
            'user' => new UserResource($user),
            'seller' => new SellerResource($vendor),
            'token' => $auth->issueToken($user),
            'onboarding_status' => 'submitted',
            'message' => 'Seller account created with pending onboarding status.',
        ], 201);
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $user = $auth->validateCredentials($request->validated('email'), $request->validated('password'));
        if (! $user || ! $user->hasPermission('seller.access')) {
            return $this->error('Invalid seller credentials.', 422);
        }

        $vendor = Vendor::where('user_id', $user->id)->first();
        if (! $vendor) {
            return $this->error('Seller profile is not available for this account.', 403);
        }
        if (in_array($vendor->status, ['suspended', 'rejected'], true)) {
            return $this->error('Seller account is not active for login.', 403);
        }

        return $this->success([
            'user' => new UserResource($user),
            'seller' => new SellerResource($vendor),
            'token' => $auth->issueToken($user),
            'portal_status' => $vendor->status === 'active' ? 'active' : 'early_access_pending',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->first();

        return $this->success([
            'user' => new UserResource($request->user()),
            'seller' => $vendor ? new SellerResource($vendor) : null,
        ]);
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->logout($request->user());

        return $this->success(['message' => 'Logged out.']);
    }
}
