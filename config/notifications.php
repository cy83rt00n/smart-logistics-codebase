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
];
