<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF token validation in tests; Inertia's version token
        // provides sufficient request integrity checking for the test suite.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
