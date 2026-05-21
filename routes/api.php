<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::prefix('v1')->group(function () {
    Route::post('/notifications/send', [NotificationController::class, 'sendMass']);
    Route::get('/subscribers/{subscriberId}/history', [NotificationController::class, 'getSubscriberHistory']);
});
