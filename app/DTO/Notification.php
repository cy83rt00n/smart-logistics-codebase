<?php

namespace App\DTO;

class Notification
{
    /**
     * @param string               $category   'transaction'|'marketing'
     * @param string               $type       'sms'|'email'
     * @param array<int, string>   $recipients
     * @param string               $sender     Sender identifier (phone number, email address)
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $category,
        public readonly string $type,
        public readonly array $recipients,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $sender = '',
        public readonly array $data = [],
    ) {
    }
}
