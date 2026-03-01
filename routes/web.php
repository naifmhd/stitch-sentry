<?php

use App\Http\Controllers\DashboardController;
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
