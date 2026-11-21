<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminOrderNotifications;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __invoke(AdminOrderNotifications $notifications): JsonResponse
    {
        return response()->json($notifications->snapshot())->header('Cache-Control', 'no-store');
    }
}
