<?php

use App\Models\User;

test('local login authenticates user id 1', function () {
    $user = User::factory()->create();

    expect($user->id)->toBe(1);

    $this->post('/local-login')
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $this->get('/dashboard')
        ->assertSuccessful();
});

test('local login redirects to login when user id 1 does not exist', function () {
    $this->post('/local-login')
        ->assertRedirect('/login');

    $this->assertGuest();
});
