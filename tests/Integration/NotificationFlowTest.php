<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Notification;
use App\Enums\NotificationStatus;
use App\Services\NotificationService;
use App\Repositories\NotificationRepository;
use App\Services\Providers\SmsProviderMock;
use App\Services\Providers\EmailProviderMock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendNotificationJob;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_send_mass_notifications()
    {
        $payload = [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipients' => ['+1234567890', '+0987654321'],
            'priority' => 5,
            'idempotency_key' => 'test_' . uniqid()
        ];

        $response = $this->postJson('/api/v1/notifications/send', $payload);

        $response->assertStatus(202);
        $response->assertJsonStructure([
            'message',
            'data' => ['total', 'dispatched', 'channel', 'priority', 'idempotency_key']
        ]);

        $this->assertDatabaseHas('notifications', [
            'subscriber_id' => '+1234567890',
            'status' => NotificationStatus::QUEUED->value,
        ]);

        Queue::assertPushed(SendNotificationJob::class, 2);
    }

    public function test_idempotency_prevents_duplicate_requests()
    {
        $idempotencyKey = 'unique_' . uniqid();

        $payload = [
            'channel' => 'email',
            'message' => 'Unique message',
            'recipients' => ['user@example.com'],
            'idempotency_key' => $idempotencyKey
        ];

        $firstResponse = $this->postJson('/api/v1/notifications/send', $payload);
        $firstResponse->assertStatus(202);

        $secondResponse = $this->postJson('/api/v1/notifications/send', $payload);
        $secondResponse->assertStatus(200);
        $secondResponse->assertJson([
            'message' => 'Request already processed'
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_can_get_subscriber_history()
    {
        $subscriberId = 'sub_' . uniqid();

        Notification::create([
            'idempotency_key' => 'key1_' . uniqid(),
            'subscriber_id' => $subscriberId,
            'channel' => 'sms',
            'message' => 'Test 1',
            'status' => NotificationStatus::DELIVERED->value
        ]);

        Notification::create([
            'idempotency_key' => 'key2_' . uniqid(),
            'subscriber_id' => $subscriberId,
            'channel' => 'email',
            'message' => 'Test 2',
            'status' => NotificationStatus::SENT->value
        ]);

        $response = $this->getJson("/api/v1/subscribers/{$subscriberId}/history");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'subscriber_id', 'channel', 'message', 'status']
            ],
            'meta' => ['current_page', 'total', 'per_page']
        ]);
    }

    public function test_notification_processing_with_provider_mock()
    {
        $idempotencyKey = 'provider_test_' . uniqid();
        $subscriberId = '+79001234567';

        $notification = Notification::create([
            'idempotency_key' => $idempotencyKey,
            'subscriber_id' => $subscriberId,
            'channel' => 'sms',
            'message' => 'Test provider message',
            'status' => NotificationStatus::QUEUED->value,
        ]);

        $repository = new NotificationRepository();
        $smsProvider = new SmsProviderMock();
        $emailProvider = new EmailProviderMock();
        $service = new NotificationService($repository, $smsProvider, $emailProvider);

        $service->processNotification($idempotencyKey);

        $updated = $repository->findByIdempotencyKey($idempotencyKey);
        $this->assertContains($updated->status, [
            NotificationStatus::SENT->value,
            NotificationStatus::DELIVERED->value,
            NotificationStatus::DROPPED->value
        ]);
    }

    public function test_email_provider_mock_returns_expected_structure()
    {
        $provider = new EmailProviderMock();
        $result = $provider->send('test@example.com', 'Test message');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status', $result);

        if ($result['success']) {
            $this->assertArrayHasKey('provider_message_id', $result);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }

    public function test_sms_provider_mock_returns_expected_structure()
    {
        $provider = new SmsProviderMock();
        $result = $provider->send('+79001234567', 'Test message');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status', $result);

        if ($result['success']) {
            $this->assertArrayHasKey('provider_message_id', $result);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }

    public function test_priority_messages_are_processed_correctly()
    {
        $payload = [
            'channel' => 'sms',
            'message' => 'High priority message',
            'recipients' => ['+1111111111'],
            'priority' => 10,
            'idempotency_key' => 'priority_' . uniqid()
        ];

        $response = $this->postJson('/api/v1/notifications/send', $payload);
        $response->assertStatus(202);

        $this->assertDatabaseHas('notifications', [
            'subscriber_id' => '+1111111111',
            'status' => NotificationStatus::QUEUED->value
        ]);
    }

    public function test_validation_fails_for_invalid_channel()
    {
        $payload = [
            'channel' => 'push',
            'message' => 'Test',
            'recipients' => ['test@example.com'],
            'idempotency_key' => 'invalid_' . uniqid()
        ];

        $response = $this->postJson('/api/v1/notifications/send', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['channel']);
    }

    public function test_validation_fails_for_empty_recipients()
    {
        $payload = [
            'channel' => 'email',
            'message' => 'Test',
            'recipients' => [],
            'idempotency_key' => 'empty_' . uniqid()
        ];

        $response = $this->postJson('/api/v1/notifications/send', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['recipients']);
    }

    public function test_validation_fails_for_missing_idempotency_key()
    {
        $payload = [
            'channel' => 'sms',
            'message' => 'Test',
            'recipients' => ['+79001234567'],
        ];

        $response = $this->postJson('/api/v1/notifications/send', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_status_transitions_work_correctly()
    {
        $idempotencyKey = 'status_test_' . uniqid();
        $subscriberId = '+79009999999';

        $notification = Notification::create([
            'idempotency_key' => $idempotencyKey,
            'subscriber_id' => $subscriberId,
            'channel' => 'email',
            'message' => 'Status test message',
            'status' => NotificationStatus::QUEUED->value,
        ]);

        $this->assertEquals(NotificationStatus::QUEUED->value, $notification->status);

        $repository = new NotificationRepository();
        $repository->updateStatus($idempotencyKey, NotificationStatus::SENT);

        $updated = $repository->findByIdempotencyKey($idempotencyKey);
        $this->assertEquals(NotificationStatus::SENT->value, $updated->status);
        $this->assertNotNull($updated->sent_at);
    }
}
