<?php

namespace Tests\Unit;

use App\DTO\Notification;
use App\Exceptions\NotificationException;
use App\Services\Channels\SmsChannel;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    public function testSendWithUnsupportedTypeThrowsException(): void
    {
        $this->expectException(NotificationException::class);

        $service      = new NotificationService(channels: []);
        $notification = new Notification(
            category: 'transaction',
            type: 'telegram',
            recipients: ['user'],
            subject: '',
            body: 'Hello',
        );

        $service->send($notification);
    }

    public function testSendWithEmptyRecipientsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service      = new NotificationService(channels: ['sms' => SmsChannel::class]);
        $notification = new Notification(
            category: 'marketing',
            type: 'sms',
            recipients: [],
            subject: '',
            body: 'Hello',
        );

        $service->send($notification);
    }

    public function testSendReturnsEmptyErrorsOnSuccess(): void
    {
        $service      = new NotificationService(channels: ['sms' => SmsChannel::class]);
        $notification = new Notification(
            category: 'transaction',
            type: 'sms',
            recipients: ['+79991234567', '+79997654321'],
            subject: '',
            body: 'Hello',
        );

        $errors = $service->send($notification);

        $this->assertSame([], $errors);
    }
}
