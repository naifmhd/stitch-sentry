<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;

Route::middleware(['guest'])->group(function () {
    Route::get('login', fn (AuthKitLoginRequest $request) => $request->redirect())->name('login');

    Route::post('local-login', function (Request $request) {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $localUser = User::query()->first();

        if ($localUser === null) {
            return redirect()->route('login');
        }

        auth()->login($localUser);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    })->name('local-login');

    Route::get('authenticate', fn (AuthKitAuthenticationRequest $request) => tap(
        redirect()->intended(route('dashboard')),
        fn () => $request->authenticate(),
    ));
});

Route::post('logout', fn (AuthKitLogoutRequest $request) => $request->logout())
    ->middleware(['auth'])->name('logout');
