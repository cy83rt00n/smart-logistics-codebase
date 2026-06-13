<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    /**
     * Mock sending an SMS by simulating real SMS provider (Twilio-like API).
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool
    {
        try {
            // Simulate API endpoint
            $apiUrl = config('notifications.sms.api_url', 'https://api.sms-provider.example/v1/send');
            $apiKey = config('notifications.sms.api_key', 'mock-api-key-12345');

            // Simulate network delay (50-200ms)
            usleep(random_int(50000, 200000));

            // Simulate random failures (5% chance)
            if (random_int(1, 100) <= 5) {
                throw new \Exception('SMS provider timeout');
            }

            // Mock HTTP request to SMS provider
            $response = [
                'success'    => true,
                'message_id' => 'SM'.bin2hex(random_bytes(16)),
                'to'         => $recipient,
                'from'       => $notification->sender,
                'status'     => 'sent',
                'timestamp'  => now()->toIso8601String(),
            ];

            Log::info('[SMS] Message sent', [
                'provider'   => 'Mock SMS Provider',
                'to'         => $recipient,
                'from'       => $notification->sender,
                'body'       => substr($notification->body, 0, 50).'...',
                'message_id' => $response['message_id'],
                'api_url'    => $apiUrl,
            ]);

            $delivery->update(['status' => 'delivered']);

            return true;
        } catch (\Throwable $e) {
            Log::error('[SMS] Send failed', [
                'to'    => $recipient,
                'error' => $e->getMessage(),
            ]);

            $delivery->update([
                'status'        => 'rejected',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}