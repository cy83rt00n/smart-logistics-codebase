<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    /**
     * Mock sending an SMS by logging it and updating delivery status.
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool
    {
        try {
            Log::info('[SMS]', [
                'to'   => $recipient,
                'body' => $notification->body,
            ]);

            $delivery->update(['status' => 'delivered']);

            return true;
        } catch (\Throwable $e) {
            $delivery->update([
                'status'        => 'rejected',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
