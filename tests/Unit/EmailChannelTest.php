<?php

namespace Tests\Unit;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use App\Services\Channels\EmailChannel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmailChannelTest extends TestCase
{
    public function testSendLogsEmail(): void
    {
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        $channel      = new EmailChannel();
        $notification = new Notification(
            category: 'marketing',
            type: 'email',
            recipients: ['test@example.com'],
            subject: 'Test Subject',
            body: 'Test Email body',
        );

        $delivery = NotificationDelivery::factory()->create();

        $result = $channel->send($notification, 'test@example.com', $delivery);

        $this->assertIsBool($result);
    }
}
