<?php

namespace Tests\Feature;

use Tests\TestCase;

class SendNotificationTest extends TestCase
{
    public function testSendMarketingSmsNotificationViaApi(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'marketing',
            'type'       => 'sms',
            'recipients' => ['+79991234567', '+79997654321'],
            'subject'    => 'Test SMS',
            'body'       => 'This is a test SMS message',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification queued for sending',
                 ]);
    }

    public function testSendMarketingEmailNotificationViaApi(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'marketing',
            'type'       => 'email',
            'recipients' => ['user1@example.com', 'user2@example.com'],
            'subject'    => 'Test Email',
            'body'       => 'This is a test email message',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification queued for sending',
                 ]);
    }

    public function testSendTransactionNotificationViaApi(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'transaction',
            'type'       => 'sms',
            'recipients' => ['+79991234567'],
            'subject'    => 'Transaction Alert',
            'body'       => 'Your payment was successful',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notification sent',
                 ]);
    }

    public function testValidationFailsWithMissingFields(): void
    {
        $response = $this->postJson('/api/notifications/send', []);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }

    public function testValidationFailsWithInvalidCategory(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'invalid',
            'type'       => 'sms',
            'recipients' => ['user'],
            'subject'    => 'Test',
            'body'       => 'Test body',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }

    public function testValidationFailsWithInvalidType(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'marketing',
            'type'       => 'telegram',
            'recipients' => ['user'],
            'subject'    => 'Test',
            'body'       => 'Test body',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }

    public function testValidationFailsWithEmptyRecipients(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'category'   => 'marketing',
            'type'       => 'sms',
            'recipients' => [],
            'subject'    => 'Test',
            'body'       => 'Test body',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }
}
