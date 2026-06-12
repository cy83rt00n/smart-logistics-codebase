<?php

namespace App\Models;

use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'category',
        'recipient',
        'subject',
        'body',
        'status',
        'error_message',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $delivery): void {
            $delivery->uuid = (string) Str::uuid();
        });
    }
}
