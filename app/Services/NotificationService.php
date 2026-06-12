<?php

namespace App\Services;

use App\DTO\Notification;
use App\Exceptions\NotificationException;
use App\Models\NotificationDelivery;

class NotificationService
{
    /**
     * @param array<string, string> $channels Map of type => channel class name
     */
    public function __construct(
        private readonly array $channels,
    ) {
    }

    /**
     * Send a notification to all recipients via the appropriate channel.
     *
     * @return array<string, list<string>> Map of recipient => error messages (empty on success)
     */
    public function send(Notification $notification): array
    {
        if ([] === $notification->recipients) {
            throw new \InvalidArgumentException('Recipients list must not be empty.');
        }

        $channelClass = $this->channels[$notification->type] ?? null;

        if (null === $channelClass) {
            throw new NotificationException("Unsupported notification type: {$notification->type}");
        }

        /** @var Channels\NotificationChannelInterface $channel */
        $channel = app($channelClass);

        $errors = [];

        foreach ($notification->recipients as $recipient) {
            // Create a delivery record with 'sent' status for each recipient
            $delivery = NotificationDelivery::create([
                'type'      => $notification->type,
                'category'  => $notification->category,
                'recipient' => $recipient,
                'subject'   => $notification->subject,
                'body'      => $notification->body,
                'status'    => 'sent',
            ]);

            try {
                $channel->send($notification, $recipient, $delivery);
            } catch (\Throwable $e) {
                $delivery->update([
                    'status'        => 'rejected',
                    'error_message' => $e->getMessage(),
                ]);

                $errors[$recipient][] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Create a queued delivery record (for marketing notifications).
     */
    public function createQueuedDelivery(Notification $notification, string $recipient): NotificationDelivery
    {
        return NotificationDelivery::create([
            'type'      => $notification->type,
            'category'  => $notification->category,
            'recipient' => $recipient,
            'subject'   => $notification->subject,
            'body'      => $notification->body,
            'status'    => 'queued',
        ]);
    }
}
