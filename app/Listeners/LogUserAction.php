<?php

namespace App\Listeners;

use App\Events\UserActionPerformed;
use App\Models\ActivityLog;

class LogUserAction
{
    public function handle(UserActionPerformed $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => $event->action,
            'description' => $event->description,
        ]);
    }
}