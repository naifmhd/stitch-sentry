<?php

use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\SubscribeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\BroadcastTestController;
use App\Http\Controllers\Dev\FeatureGateCheckController;
use App\Http\Controllers\Dev\PingQueueController;
use App\Http\Controllers\Organizations\OrgSettingsController;
use App\Http\Controllers\Organizations\OrgSwitcherController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::inertia('/', 'Welcome')->name('home');

$authenticatedMiddleware = array_filter([
    'auth',
    app()->environment(['local', 'testing']) ? null : ValidateSessionWithWorkOS::class,
]);

Route::middleware($authenticatedMiddleware)->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('organizations/switch', OrgSwitcherController::class)->name('organizations.switch');
    Route::get('organizations/{organization}/settings', [OrgSettingsController::class, 'show'])->name('organizations.settings');

    Route::get('organizations/{organization}/credits', \App\Http\Controllers\Billing\CreditsController::class)->name('billing.credits');

    // Billing
    Route::get('billing', BillingController::class)->name('billing');
    Route::post('billing/subscribe/{planSlug}', SubscribeController::class)->name('billing.subscribe');
});

if (app()->environment(['local', 'testing'])) {
    Route::middleware($authenticatedMiddleware)->group(function () {
        Route::post('dev/ping', PingQueueController::class)->name('dev.ping');
        Route::get('dev/reverb-test', [BroadcastTestController::class, 'show'])->name('dev.reverb-test');
        Route::post('dev/reverb-test', [BroadcastTestController::class, 'broadcast'])->name('dev.broadcast-test');
        Route::post('dev/feature-gate-check', FeatureGateCheckController::class)->name('dev.feature-gate-check');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
