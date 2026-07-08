<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function role(string $name, array $defaults): Role
    {
        return Role::firstOrCreate(['name' => $name], $defaults);
    }

    public function issueToken(User $user): string
    {
        $token = Str::random(64);

        $user->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'last_login_at' => now(),
        ])->save();

        return $token;
    }

    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        return $user && Hash::check($password, $user->password) ? $user : null;
    }

    public function logout(User $user): void
    {
        $user->forceFill(['api_token_hash' => null])->save();
    }
}
