<?php

namespace App\Services\Channels;

use App\DTO\Notification;
use App\Models\NotificationDelivery;

interface NotificationChannelInterface
{
    /**
     * Send a notification to a single recipient.
     */
    public function send(Notification $notification, string $recipient, NotificationDelivery $delivery): bool;
}
