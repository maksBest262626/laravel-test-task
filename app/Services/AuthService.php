<?php

namespace App\Services;

use App\Events\UserActionPerformed;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create($data);
        $token = $user->createToken('auth-token')->plainTextToken;

        UserActionPerformed::dispatch($user, 'register', 'User registered');

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        UserActionPerformed::dispatch($user, 'login', 'User logged in');

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        UserActionPerformed::dispatch($user, 'logout', 'User logged out');

        $user->currentAccessToken()->delete();
    }
}