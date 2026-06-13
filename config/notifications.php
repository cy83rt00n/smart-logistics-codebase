<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Map notification types to channel handler classes.
    |
    */

    'channels' => [
        'sms'   => App\Services\Channels\SmsChannel::class,
        'email' => App\Services\Channels\EmailChannel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configuration
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'api_url' => env('SMS_PROVIDER_URL', 'https://api.sms-provider.example/v1/send'),
        'api_key' => env('SMS_PROVIDER_KEY', 'mock-api-key-12345'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Provider Configuration
    |--------------------------------------------------------------------------
    */

    'email' => [
        'api_url' => env('EMAIL_PROVIDER_URL', 'https://api.email-provider.example/v1/send'),
        'api_key' => env('EMAIL_PROVIDER_KEY', 'mock-api-key-67890'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection
    |--------------------------------------------------------------------------
    |
    | Configuration for RabbitMQ message broker.
    |
    */

    'rabbitmq' => [
        'host'     => env('RABBITMQ_HOST', 'queue-broker'),
        'port'     => env('RABBITMQ_PORT', 5672),
        'user'     => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost'    => env('RABBITMQ_VHOST', '/'),
        'queue'    => env('RABBITMQ_QUEUE', 'notifications'),
    ],
];
