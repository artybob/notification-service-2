<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    public function test_idempotency_service_marks_and_checks_keys()
    {
        $service = new IdempotencyService();
        $key = 'test_key_' . uniqid();

        $this->assertFalse($service->isProcessed($key));

        $service->markAsProcessed($key, ['result' => 'success']);

        $this->assertTrue($service->isProcessed($key));
    }

    public function test_idempotency_service_returns_cached_result()
    {
        $service = new IdempotencyService();
        $key = 'cached_key_' . uniqid();
        $expectedResult = ['status' => 'completed', 'id' => 123];

        $service->markAsProcessed($key, $expectedResult);
        $cachedResult = $service->getCachedResult($key);

        $this->assertEquals($expectedResult, $cachedResult);
    }

    public function test_idempotency_works_in_full_api_flow()
    {
        $idempotencyKey = 'full_flow_' . uniqid();

        $payload = [
            'channel' => 'sms',
            'message' => 'Idempotency test',
            'recipients' => ['+79000000000'],
            'idempotency_key' => $idempotencyKey
        ];

        $firstResponse = $this->postJson('/api/v1/notifications/send', $payload);
        $firstResponse->assertStatus(202);

        $secondResponse = $this->postJson('/api/v1/notifications/send', $payload);
        $secondResponse->assertStatus(200);
        $secondResponse->assertJson(['message' => 'Request already processed']);

        $this->assertDatabaseCount('notifications', 1);
    }
}
