<?php

namespace App\Traits;

/**
 * @OA\Info(
 *     title="Notification Service API",
 *     version="1.0.0",
 *     description="Микросервис для массовой рассылки SMS/Email уведомлений с приоритетами",
 *     @OA\Contact(
 *         email="support@notificationservice.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Локальный сервер"
 * )
 *
 * @OA\Server(
 *     url="https://api.notificationservice.com",
 *     description="Production сервер"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Endpoints для отправки уведомлений"
 * )
 *
 * @OA\Tag(
 *     name="Subscribers",
 *     description="Endpoints для истории подписчиков"
 * )
 *
 * @OA\Tag(
 *     name="System",
 *     description="Системные endpoints"
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="idempotency_key", type="string", example="unique_123"),
 *     @OA\Property(property="subscriber_id", type="string", example="+79001234567"),
 *     @OA\Property(property="channel", type="string", enum={"sms","email"}, example="sms"),
 *     @OA\Property(property="message", type="string", example="Test message"),
 *     @OA\Property(property="status", type="string", enum={"queued","sent","delivered","dropped"}, example="queued"),
 *     @OA\Property(property="retry_count", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="SendMassNotificationRequest",
 *     required={"channel","message","recipients","idempotency_key"},
 *     @OA\Property(property="channel", type="string", enum={"sms","email"}, example="sms"),
 *     @OA\Property(property="message", type="string", maxLength=5000, example="Your verification code: 123456"),
 *     @OA\Property(property="recipients", type="array", @OA\Items(type="string"), minItems=1, maxItems=1000, example={"+79001234567","+79007654321"}),
 *     @OA\Property(property="priority", type="integer", minimum=0, maximum=10, example=5),
 *     @OA\Property(property="idempotency_key", type="string", maxLength=255, example="unique_request_id_123")
 * )
 *
 * @OA\Schema(
 *     schema="SendMassNotificationResponse",
 *     @OA\Property(property="message", type="string", example="Notifications queued successfully"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="total", type="integer", example=2),
 *         @OA\Property(property="dispatched", type="integer", example=2),
 *         @OA\Property(property="channel", type="string", example="sms"),
 *         @OA\Property(property="priority", type="integer", example=5),
 *         @OA\Property(property="idempotency_key", type="string", example="unique_request_id_123")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="errors", type="object"),
 *     @OA\Property(property="error", type="string", nullable=true)
 * )
 */
trait SwaggerAnnotations
{
    //
}
