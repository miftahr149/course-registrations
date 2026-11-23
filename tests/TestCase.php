<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The CI test job doesn't build the frontend, so there is no Vite manifest to read.
        $this->withoutVite();
    }
}
