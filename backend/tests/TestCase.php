<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mimic the SPA so Sanctum treats requests as stateful (session-based).
        $this->withHeader('Origin', 'http://localhost:5173');
    }
}
