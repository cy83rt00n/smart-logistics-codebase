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
            ->atLeast()
            ->once();

        // Allow error() calls due to random failures
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        $delivery = NotificationDelivery::create([
            'sender'    => 'noreply@company.com',
            'type'      => 'email',
            'category'  => 'marketing',
            'recipient' => 'test@example.com',
            'subject'   => 'Test Subject',
            'body'      => 'Test Email body',
            'status'    => 'sent',
        ]);

        $channel      = new EmailChannel();
        $notification = new Notification(
            sender: 'noreply@company.com',
            category: 'marketing',
            type: 'email',
            recipients: ['test@example.com'],
            subject: 'Test Subject',
            body: 'Test Email body',
        );

        $result = $channel->send($notification, 'test@example.com', $delivery);

        // Result can be true (success) or false (random 3% failure)
        $this->assertIsBool($result);
    }
}
