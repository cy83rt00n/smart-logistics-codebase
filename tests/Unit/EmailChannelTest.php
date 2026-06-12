<?php

namespace Tests\Unit;

use App\DTO\Notification;
use App\Services\Channels\EmailChannel;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmailChannelTest extends TestCase
{
    public function testSendLogsEmail(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('[EMAIL]', [
                'to'      => 'test@example.com',
                'subject' => 'Test Subject',
                'body'    => 'Test Email body',
            ]);

        $channel      = new EmailChannel();
        $notification = new Notification(
            category: 'marketing',
            type: 'email',
            recipients: ['test@example.com'],
            subject: 'Test Subject',
            body: 'Test Email body',
        );

        $result = $channel->send($notification, 'test@example.com');

        $this->assertTrue($result);
    }
}
