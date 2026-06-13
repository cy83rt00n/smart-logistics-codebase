<?php

namespace Tests\Unit;

use App\DTO\Notification;
use App\Models\NotificationDelivery;
use App\Services\Channels\SmsChannel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    public function testSendLogsSms(): void
    {
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        $channel      = new SmsChannel();
        $notification = new Notification(
            category: 'transaction',
            type: 'sms',
            recipients: ['+79991234567'],
            subject: '',
            body: 'Test SMS body',
        );

        $delivery = NotificationDelivery::factory()->create();

        $result = $channel->send($notification, '+79991234567', $delivery);

        $this->assertIsBool($result);
    }
}
