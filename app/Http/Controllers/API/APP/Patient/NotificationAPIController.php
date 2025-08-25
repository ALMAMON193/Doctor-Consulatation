<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationAPIController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get();
        return $this->sendResponse (
            \App\Http\Resources\APP\Patient\NotificationResource::collection($notifications),
            __('Doctor notifications retrieved successfully.')
        );
    }

    // Mark notification as read
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return $this->sendError(__('Notification not found.'));
        }
        $notification->markAsRead();
        return $this->sendResponse ([],__('Notification marked as read'));
    }
}
