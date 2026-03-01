<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\BroadcastTestController;
use App\Http\Controllers\Dev\PingQueueController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::inertia('/', 'Welcome')->name('home');

$authenticatedMiddleware = array_filter([
    'auth',
    app()->environment(['local', 'testing']) ? null : ValidateSessionWithWorkOS::class,
]);

Route::middleware($authenticatedMiddleware)->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

if (app()->environment(['local', 'testing'])) {
    Route::middleware($authenticatedMiddleware)->group(function () {
        Route::post('dev/ping', PingQueueController::class)->name('dev.ping');
        Route::get('dev/reverb-test', [BroadcastTestController::class, 'show'])->name('dev.reverb-test');
        Route::post('dev/reverb-test', [BroadcastTestController::class, 'broadcast'])->name('dev.broadcast-test');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
