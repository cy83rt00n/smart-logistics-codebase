<?php

namespace Tests\Unit;

use App\DTO\Notification;
use App\Services\Channels\SmsChannel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    public function testSendLogsSms(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('[SMS]', [
                'to'   => '+79991234567',
                'body' => 'Test SMS body',
            ]);

        $channel      = new SmsChannel();
        $notification = new Notification(
            category: 'transaction',
            type: 'sms',
            recipients: ['+79991234567'],
            subject: '',
            body: 'Test SMS body',
        );

        $result = $channel->send($notification, '+79991234567');

        $this->assertTrue($result);
    }
}
