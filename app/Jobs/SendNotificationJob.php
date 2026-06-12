<?php

namespace App\Jobs;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * @param array<int, int> $deliveryIds IDs of NotificationDelivery records to process
     */
    public function __construct(
        public array $deliveryIds,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $service): void
    {
        $deliveries = NotificationDelivery::whereIn('id', $this->deliveryIds)->get();

        foreach ($deliveries as $delivery) {
            $notification = new Notification(
                category: $delivery->category,
                type: $delivery->type,
                recipients: [$delivery->recipient],
                subject: $delivery->subject,
                body: $delivery->body,
            );

            $delivery->update(['status' => 'sent']);

            try {
                $service->send($notification);
            } catch (\Throwable $e) {
                $delivery->update([
                    'status'        => 'rejected',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }
}
