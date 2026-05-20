<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class IdempotencyService
{
    private const TTL_SECONDS = 86400;

    public function isProcessed(string $idempotencyKey): bool
    {
        return Redis::exists("idempotency:{$idempotencyKey}") === 1;
    }

    public function markAsProcessed(string $idempotencyKey, array $result): void
    {
        Redis::setex(
            "idempotency:{$idempotencyKey}",
            self::TTL_SECONDS,
            json_encode($result)
        );
    }

    public function getCachedResult(string $idempotencyKey): ?array
    {
        $cached = Redis::get("idempotency:{$idempotencyKey}");
        return $cached ? json_decode($cached, true) : null;
    }
}
