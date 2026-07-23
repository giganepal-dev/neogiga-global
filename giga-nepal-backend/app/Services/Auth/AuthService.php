<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function role(string $name, array $defaults): Role
    {
        return Role::firstOrCreate(['name' => $name], $defaults);
    }

    public function issueToken(User $user, array $abilities = ['*']): string
    {
        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return $token;
    }

    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        return $user && Hash::check($password, $user->password) ? $user : null;
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
