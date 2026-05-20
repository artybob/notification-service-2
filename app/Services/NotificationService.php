<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Enums\NotificationStatus;
use App\Services\Providers\SmsProviderMock;
use App\Services\Providers\EmailProviderMock;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private NotificationRepository $repository,
        private SmsProviderMock $smsProvider,
        private EmailProviderMock $emailProvider
    ) {}

    public function processNotification(string $idempotencyKey): void
    {
        $notification = $this->repository->findByIdempotencyKey($idempotencyKey);

        if (!$notification || $notification->status !== NotificationStatus::QUEUED->value) {
            return;
        }

        try {
            $result = $this->sendViaProvider($notification);

            if ($result['success']) {
                $this->repository->updateStatus(
                    $idempotencyKey,
                    NotificationStatus::SENT,
                    $result
                );

                if (isset($result['final_status']) && $result['final_status'] === 'delivered') {
                    $this->repository->updateStatus(
                        $idempotencyKey,
                        NotificationStatus::DELIVERED,
                        $result
                    );
                }
            } else {
                $this->repository->updateStatus(
                    $idempotencyKey,
                    NotificationStatus::DROPPED,
                    $result
                );

                Log::warning('Notification failed', [
                    'idempotency_key' => $idempotencyKey,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->repository->updateStatus(
                $idempotencyKey,
                NotificationStatus::DROPPED,
                ['error' => $e->getMessage()]
            );

            Log::error('Provider exception', [
                'idempotency_key' => $idempotencyKey,
                'exception' => $e->getMessage()
            ]);
        }
    }

    private function sendViaProvider($notification): array
    {
        if ($notification->channel === 'sms') {
            return $this->smsProvider->send(
                $notification->subscriber_id,
                $notification->message
            );
        }

        return $this->emailProvider->send(
            $notification->subscriber_id,
            $notification->message
        );
    }
}
