<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\DB;

class NotificationRepository
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function updateStatus(string $idempotencyKey, NotificationStatus $status, array $providerResponse = null): bool
    {
        $updateData = ['status' => $status->value];

        if ($status === NotificationStatus::SENT) {
            $updateData['sent_at'] = now();
        }

        if ($status === NotificationStatus::DELIVERED) {
            $updateData['delivered_at'] = now();
        }

        if ($providerResponse !== null) {
            $updateData['provider_response'] = $providerResponse;
        }

        if ($status === NotificationStatus::DROPPED) {
            $updateData['retry_count'] = DB::raw('retry_count + 1');
        }

        return Notification::where('idempotency_key', $idempotencyKey)
                ->update($updateData) > 0;
    }

    public function getBySubscriber(string $subscriberId, int $limit = 100)
    {
        return Notification::where('subscriber_id', $subscriberId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Notification
    {
        return Notification::where('idempotency_key', $idempotencyKey)->first();
    }
}
