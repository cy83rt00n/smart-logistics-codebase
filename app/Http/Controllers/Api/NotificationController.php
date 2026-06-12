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

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Send a notification via the specified channel.
     */
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
