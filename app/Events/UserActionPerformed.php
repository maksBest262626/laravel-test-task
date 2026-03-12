<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserActionPerformed
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $action,
        public readonly string $description = '',
    ) {}
}