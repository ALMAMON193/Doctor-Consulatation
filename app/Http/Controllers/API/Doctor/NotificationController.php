<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\Notification\NotificationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get();
        return $this->sendResponse (
            NotificationResource::collection($notifications),
            __('Doctor notifications retrieved successfully.')
        );
    }

    // Mark notification as read
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marked as read']);
    }
}
