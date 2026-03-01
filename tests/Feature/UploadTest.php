<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected away from the upload page', function () {
    $this->get('/upload')->assertRedirect('/login');
});

test('authenticated users can access the upload page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/upload')
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('upload/Index')
                ->has('maxFileSizeMb')
        );
});

test('upload page passes a positive max file size from the feature gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/upload')
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('upload/Index')
                ->where('maxFileSizeMb', fn (mixed $value) => is_int($value) && $value > 0)
        );
});
