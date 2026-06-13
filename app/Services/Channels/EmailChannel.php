<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    /**
     * Mock sending an email by simulating real email provider (SendGrid-like API).
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool
    {
        try {
            // Simulate API endpoint
            $apiUrl = config('notifications.email.api_url', 'https://api.email-provider.example/v1/send');
            $apiKey = config('notifications.email.api_key', 'mock-api-key-67890');

            // Simulate network delay (100-300ms)
            usleep(random_int(100000, 300000));

            // Simulate random failures (3% chance)
            if (random_int(1, 100) <= 3) {
                throw new \Exception('Email provider rate limit exceeded');
            }

            // Mock HTTP request to email provider
            $response = [
                'success'    => true,
                'message_id' => 'EM'.bin2hex(random_bytes(16)),
                'to'         => $recipient,
                'from'       => $notification->sender,
                'subject'    => $notification->subject,
                'status'     => 'queued',
                'timestamp'  => now()->toIso8601String(),
            ];

            Log::info('[Email] Message sent', [
                'provider'   => 'Mock Email Provider',
                'to'         => $recipient,
                'from'       => $notification->sender,
                'subject'    => $notification->subject,
                'body'       => substr($notification->body, 0, 100).'...',
                'message_id' => $response['message_id'],
                'api_url'    => $apiUrl,
            ]);

            $delivery->update(['status' => 'delivered']);

            return true;
        } catch (\Throwable $e) {
            Log::error('[Email] Send failed', [
                'to'      => $recipient,
                'subject' => $notification->subject,
                'error'   => $e->getMessage(),
            ]);

            $delivery->update([
                'status'        => 'rejected',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}