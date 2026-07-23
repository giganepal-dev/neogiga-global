<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Affiliate\AffiliateService;
use App\Services\Marketing\AccountCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponses;

    public function register(Request $request, AccountCommunicationService $communications): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'description' => 'Default buyer account',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ],
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $role->id,
        ]);

        $this->bindReferral($user, $request);
        $communications->registration($user);

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials.', 422);
        }

        $this->bindReferral($user, $request);

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->userPayload($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(['message' => 'Logged out.']);
    }

    /**
     * Bind a pending referral attribution to the authenticated user, if a
     * visitor token was carried from the frontend. Guarded — never blocks auth.
     */
    private function bindReferral(User $user, Request $request): void
    {
        $token = $request->input('visitor_token') ?? $request->cookie('ng_ref');

        if (is_string($token) && $token !== '') {
            try {
                app(AffiliateService::class)->attributeUser(mb_substr($token, 0, 80), $user->id);
            } catch (\Throwable) {
                // referral binding is non-critical to authentication
            }
        }
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('role');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->only(['id', 'name', 'display_name']),
        ];
    }
}
