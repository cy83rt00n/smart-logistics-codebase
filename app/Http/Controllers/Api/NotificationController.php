<?php

namespace App\Http\Controllers\Api;

use App\DTO\Notification;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationDelivery;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Smart Logistics — Notification Service API',
    description: 'API для отправки уведомлений через SMS и Email с использованием мок-провайдеров.',
)]
#[OA\Server(
    url: '/api',
    description: 'API base URL',
)]
#[OA\Schema(
    schema: 'SendNotificationRequest',
    description: 'Request body for sending a notification',
    required: ['category', 'type', 'recipients', 'subject', 'body'],
    properties: [
        new OA\Property(property: 'category', type: 'string', enum: ['transaction', 'marketing'], description: 'Notification category'),
        new OA\Property(property: 'type', type: 'string', enum: ['sms', 'email'], description: 'Delivery channel'),
        new OA\Property(property: 'recipients', type: 'array', items: new OA\Items(type: 'string'), description: 'List of recipients'),
        new OA\Property(property: 'subject', type: 'string', maxLength: 255, description: 'Notification subject'),
        new OA\Property(property: 'body', type: 'string', description: 'Notification body'),
        new OA\Property(property: 'data', type: 'object', nullable: true, description: 'Additional data'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'SendNotificationResponse',
    description: 'Response for sending a notification',
    properties: [
        new OA\Property(property: 'success', type: 'boolean'),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', type: 'object', nullable: true, description: 'Per-recipient errors (if any)'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'DeliveryStatusResponse',
    description: 'Delivery status response',
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'sent', 'failed']),
        new OA\Property(property: 'recipient', type: 'string'),
        new OA\Property(property: 'type', type: 'string', enum: ['sms', 'email']),
        new OA\Property(property: 'category', type: 'string', enum: ['transaction', 'marketing']),
        new OA\Property(property: 'error_message', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Send a notification via the specified channel.
     *
     * Transactional notifications are sent synchronously (high priority).
     * Marketing notifications are queued and processed asynchronously.
     */
    #[OA\Post(
        path: '/notifications/send',
        summary: 'Send a notification',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SendNotificationRequest'),
        ),
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification sent or queued successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SendNotificationResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 500,
                description: 'Some notifications failed to send',
                content: new OA\JsonContent(ref: '#/components/schemas/SendNotificationResponse'),
            ),
        ],
    )]
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category'     => ['required', 'string', 'in:transaction,marketing'],
            'type'         => ['required', 'string', 'in:sms,email'],
            'recipients'   => ['required', 'array', 'min:1'],
            'recipients.*' => ['required', 'string'],
            'subject'      => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $notification = new Notification(
            category: $request->input('category'),
            type: $request->input('type'),
            recipients: $request->input('recipients'),
            subject: $request->input('subject'),
            body: $request->input('body'),
            data: $request->input('data', []),
        );

        if ('transaction' === $notification->category) {
            // Transactional — highest priority, sent synchronously without delay
            $errors = $this->notificationService->send($notification);

            if ([] !== $errors) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some notifications failed',
                    'errors'  => $errors,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent',
            ]);
        }

        // Marketing — create queued delivery records and dispatch job
        $deliveryIds = [];

        foreach ($notification->recipients as $recipient) {
            $delivery      = $this->notificationService->createQueuedDelivery($notification, $recipient);
            $deliveryIds[] = $delivery->id;
        }

        SendNotificationJob::dispatch($deliveryIds);

        return response()->json([
            'success' => true,
            'message' => 'Notification queued for sending',
        ]);
    }

    /**
     * Get delivery status by UUID.
     */
    #[OA\Get(
        path: '/notifications/status/{uuid}',
        summary: 'Get delivery status',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                description: 'Delivery UUID',
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Delivery status',
                content: new OA\JsonContent(ref: '#/components/schemas/DeliveryStatusResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Delivery not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification delivery not found'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function status(string $uuid): JsonResponse
    {
        $delivery = NotificationDelivery::where('uuid', $uuid)->first();

        if (null === $delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Notification delivery not found',
            ], 404);
        }

        return response()->json([
            'uuid'          => $delivery->uuid,
            'status'        => $delivery->status,
            'recipient'     => $delivery->recipient,
            'type'          => $delivery->type,
            'category'      => $delivery->category,
            'error_message' => $delivery->error_message,
            'created_at'    => $delivery->created_at,
            'updated_at'    => $delivery->updated_at,
        ]);
    }
}