<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestDatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_suite_uses_a_dedicated_testing_database(): void
    {
        $primaryDatabasePath = base_path('database/database.sqlite');
        $configuredDatabasePath = config('database.connections.sqlite.database');

        $this->assertNotSame(realpath($primaryDatabasePath), realpath($configuredDatabasePath));
        $this->assertStringContainsString('/database/testing/', str_replace('\\', '/', $configuredDatabasePath));
        $this->assertSame('sqlite', config('database.default'));
    }
}
