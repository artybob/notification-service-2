<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\NotificationService;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $idempotencyKey,
        private int $priority = 0
    ) {
        $this->onQueue('notifications');
        $this->setPriority($priority);
    }

    private function setPriority(int $priority): void
    {
        if ($priority > 0) {
            $this->onConnection('rabbitmq');
        }
    }

    public function handle(NotificationService $service): void
    {
        $service->processNotification($this->idempotencyKey);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Job failed permanently', [
            'idempotency_key' => $this->idempotencyKey,
            'error' => $e->getMessage()
        ]);
    }
}
