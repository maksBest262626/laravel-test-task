<?php

namespace App\Services;

use App\Events\UserActionPerformed;
use App\Models\User;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        UserActionPerformed::dispatch($user, 'profile_updated', 'Profile updated');

        return $user;
    }

    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}