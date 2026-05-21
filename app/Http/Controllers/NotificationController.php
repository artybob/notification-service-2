<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMassNotificationRequest;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use App\Repositories\NotificationRepository;
use App\Services\IdempotencyService;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $idempotencyService;
    protected $repository;

    public function __construct(
        IdempotencyService $idempotencyService,
        NotificationRepository $repository
    ) {
        $this->idempotencyService = $idempotencyService;
        $this->repository = $repository;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications/send",
     *     summary="Массовая рассылка уведомлений",
     *     description="Отправляет SMS или Email сообщения нескольким получателям",
     *     operationId="sendMassNotifications",
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SendMassNotificationRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Уведомления успешно поставлены в очередь",
     *         @OA\JsonContent(ref="#/components/schemas/SendMassNotificationResponse")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Запрос уже был обработан (дедубликация)"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
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
                $notification = $this->repository->create([
                    'idempotency_key' => $validated['idempotency_key'] . ':' . $recipient,
                    'subscriber_id' => $recipient,
                    'channel' => $validated['channel'],
                    'message' => $validated['message'],
                    'status' => NotificationStatus::QUEUED->value,
                ]);

                SendNotificationJob::dispatch($notification->idempotency_key, $validated['priority'] ?? 0)
                    ->onQueue('notifications');

                $dispatched[] = $recipient;
            }

            DB::commit();
            $this->idempotencyService->markAsProcessed($validated['idempotency_key'], []);

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

    /**
     * @OA\Get(
     *     path="/api/v1/subscribers/{subscriberId}/history",
     *     summary="История уведомлений подписчика",
     *     description="Возвращает все уведомления для указанного подписчика",
     *     operationId="getSubscriberHistory",
     *     tags={"Subscribers"},
     *     @OA\Parameter(
     *         name="subscriberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Идентификатор подписчика (телефон или email)"
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=50, maximum=500),
     *         description="Количество записей на странице"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="subscriber_id", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notification")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function getSubscriberHistory($subscriberId)
    {
        $limit = request()->get('limit', 50);
        $notifications = $this->repository->getBySubscriber($subscriberId, $limit);

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
