<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMassNotificationRequest;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function sendMass(SendMassNotificationRequest $request)
    {
        $validated = $request->validated();

        return response()->json([
            'message' => 'Notifications queued successfully',
            'data' => [
                'total' => count($validated['recipients']),
                'dispatched' => count($validated['recipients']),
                'channel' => $validated['channel'],
                'priority' => $validated['priority'] ?? 0,
                'idempotency_key' => $validated['idempotency_key']
            ]
        ], 202);
    }

    public function getSubscriberHistory($subscriberId)
    {
        return response()->json([
            'subscriber_id' => $subscriberId,
            'notifications' => []
        ]);
    }
}
