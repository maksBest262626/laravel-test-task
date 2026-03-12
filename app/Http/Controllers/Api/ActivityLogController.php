<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $request->user()
            ->activityLogs()
            ->orderByDesc('created_at')
            ->paginate(20);

        return ActivityLogResource::collection($logs);
    }
}