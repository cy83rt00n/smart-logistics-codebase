<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/notifications/send', [NotificationController::class, 'send']);
Route::get('/notifications/{uuid}/status', [NotificationController::class, 'status']);
