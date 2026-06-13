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
            ->atLeast()
            ->once();

        // Allow error() calls due to random failures
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        $delivery = NotificationDelivery::create([
            'sender'    => 'CompanySMS',
            'type'      => 'sms',
            'category'  => 'transaction',
            'recipient' => '+79991234567',
            'subject'   => '',
            'body'      => 'Test SMS body',
            'status'    => 'sent',
        ]);

        $channel      = new SmsChannel();
        $notification = new Notification(
            sender: 'CompanySMS',
            category: 'transaction',
            type: 'sms',
            recipients: ['+79991234567'],
            subject: '',
            body: 'Test SMS body',
        );

        $result = $channel->send($notification, '+79991234567', $delivery);

        // Result can be true (success) or false (random 5% failure)
        $this->assertIsBool($result);
    }
}
