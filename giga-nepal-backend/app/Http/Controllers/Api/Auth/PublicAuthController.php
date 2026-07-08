<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAuthController extends Controller
{
    use ApiResponses;

    public function register(RegisterRequest $request, AuthService $auth): JsonResponse
    {
        $role = $auth->role('customer', [
            'display_name' => 'Customer',
            'description' => 'Buyer account',
            'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
            'is_active' => true,
        ]);

        $data = $request->validated();
        unset($data['phone']);

        $user = User::create($data + ['role_id' => $role->id]);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $auth->issueToken($user),
            'email_verification' => 'placeholder_pending',
        ], 201);
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $user = $auth->validateCredentials($request->validated('email'), $request->validated('password'));

        if (! $user) {
            return $this->error('Invalid credentials.', 422);
        }

        return $this->success([
            'user' => new UserResource($user),
            'token' => $auth->issueToken($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->logout($request->user());

        return $this->success(['message' => 'Logged out.']);
    }
}
