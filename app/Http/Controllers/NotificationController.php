<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMassNotificationRequest;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use App\Services\IdempotencyService;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $idempotencyService;

    public function __construct(IdempotencyService $idempotencyService)
    {
        $this->idempotencyService = $idempotencyService;
    }

    public function sendMass(SendMassNotificationRequest $request)
    {
        $validated = $request->validated();

        if ($this->idempotencyService->isProcessed($validated['idempotency_key'])) {
            return response()->json([
                'message' => 'Request already processed',
            ], 200);
        }

        $dispatched = [];

        DB::beginTransaction();
        try {
            foreach ($validated['recipients'] as $recipient) {
                $notification = Notification::create([
                    'idempotency_key' => $validated['idempotency_key'] . ':' . $recipient,
                    'subscriber_id' => $recipient,
                    'channel' => $validated['channel'],
                    'message' => $validated['message'],
                    'status' => NotificationStatus::QUEUED->value,
                ]);

                SendNotificationJob::dispatch($notification)
                    ->onQueue('notifications');

                $dispatched[] = $recipient;
            }

            DB::commit();
            $this->idempotencyService->markAsProcessed($validated['idempotency_key']);

            return response()->json([
                'message' => 'Notifications queued successfully',
                'data' => [
                    'total' => count($validated['recipients']),
                    'dispatched' => count($dispatched),
                    'channel' => $validated['channel'],
                    'priority' => $validated['priority'] ?? 0,
                    'idempotency_key' => $validated['idempotency_key']
                ]
            ], 202);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to queue notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSubscriberHistory($subscriberId)
    {
        $notifications = Notification::where('subscriber_id', $subscriberId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'subscriber_id' => $subscriberId,
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
            ]
        ]);
    }
}
