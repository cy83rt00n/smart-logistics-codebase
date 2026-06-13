<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    /**
     * Mock sending an email by simulating a real email provider (SendGrid-like API).
     *
     * Uses Http::fake() to emulate the external provider call with realistic
     * responses, latency, and occasional failures.
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool
    {
        $apiUrl = config('notifications.email.api_url', 'https://api.email-provider.example/v1/send');
        $apiKey = config('notifications.email.api_key', 'mock-api-key-67890');

        // Simulate network delay (100–300 ms)
        usleep(random_int(100000, 300000));

        // Simulate random failures (3 % chance)
        if (random_int(1, 100) <= 3) {
            $delivery->update([
                'status'        => 'rejected',
                'error_message' => 'Email provider rate limit exceeded',
            ]);

            Log::error('[Email] Send failed', [
                'to'      => $recipient,
                'subject' => $notification->subject,
                'error'   => 'Email provider rate limit exceeded',
            ]);

            return false;
        }

        try {
            // Emulate the HTTP call to the email provider
            Http::fake([
                $apiUrl => function () use ($recipient, $notification): Response {
                    $messageId = 'EM'.bin2hex(random_bytes(16));

                    return Http::response([
                        'success'    => true,
                        'message_id' => $messageId,
                        'to'         => $recipient,
                        'from'       => $notification->sender ?: 'noreply@smartlogistics.app',
                        'subject'    => $notification->subject,
                        'status'     => 'queued',
                        'timestamp'  => now()->toIso8601String(),
                    ], 200, ['Content-Type' => 'application/json']);
                },
            ]);

            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->post($apiUrl, [
                    'to'      => $recipient,
                    'from'    => $notification->sender ?: 'noreply@smartlogistics.app',
                    'subject' => $notification->subject,
                    'html'    => $notification->body,
                ]);

            if (! $response->successful()) {
                throw new \Exception('Email provider returned status '.$response->status());
            }

            $payload = $response->json();

            Log::info('[Email] Message sent', [
                'provider'   => 'Mock Email Provider',
                'to'         => $recipient,
                'from'       => $notification->sender,
                'subject'    => $notification->subject,
                'body'       => substr($notification->body, 0, 100).'...',
                'message_id' => $payload['message_id'] ?? 'N/A',
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
