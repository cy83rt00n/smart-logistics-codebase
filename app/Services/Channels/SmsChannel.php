<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    /**
     * Mock sending an SMS by simulating a real SMS provider (Twilio-like API).
     *
     * Uses Http::fake() to emulate the external provider call with realistic
     * responses, latency, and occasional failures.
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool
    {
        $apiUrl = config('notifications.sms.api_url', 'https://api.sms-provider.example/v1/send');
        $apiKey = config('notifications.sms.api_key', 'mock-api-key-12345');

        // Simulate network delay (50–200 ms)
        usleep(random_int(50000, 200000));

        // Simulate random failures (5 % chance)
        if (random_int(1, 100) <= 5) {
            $delivery->update([
                'status'        => 'rejected',
                'error_message' => 'SMS provider timeout',
            ]);

            Log::error('[SMS] Send failed', [
                'to'    => $recipient,
                'error' => 'SMS provider timeout',
            ]);

            return false;
        }

        try {
            // Emulate the HTTP call to the SMS provider
            Http::fake([
                $apiUrl => function () use ($recipient, $notification): Response {
                    $messageId = 'SM'.bin2hex(random_bytes(16));

                    return Http::response([
                        'success'    => true,
                        'message_id' => $messageId,
                        'to'         => $recipient,
                        'from'       => $notification->sender ?: 'SmartLogistics',
                        'status'     => 'sent',
                        'timestamp'  => now()->toIso8601String(),
                    ], 200, ['Content-Type' => 'application/json']);
                },
            ]);

            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->post($apiUrl, [
                    'to'      => $recipient,
                    'from'    => $notification->sender ?: 'SmartLogistics',
                    'message' => $notification->body,
                ]);

            if (! $response->successful()) {
                throw new \Exception('SMS provider returned status '.$response->status());
            }

            $payload = $response->json();

            Log::info('[SMS] Message sent', [
                'provider'   => 'Mock SMS Provider',
                'to'         => $recipient,
                'from'       => $notification->sender,
                'body'       => substr($notification->body, 0, 50).'...',
                'message_id' => $payload['message_id'] ?? 'N/A',
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
