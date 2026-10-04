<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test nggak punya host MySQL beneran buat dibikinin database.
        config(['app.provision_databases' => false]);
    }
}
