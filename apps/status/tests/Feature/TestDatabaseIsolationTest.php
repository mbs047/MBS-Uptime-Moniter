<?php

namespace Tests\Feature;

use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class TestDatabaseIsolationTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_suite_uses_a_dedicated_testing_database(): void
    {
        $primaryDatabasePath = base_path('database/database.sqlite');
        $configuredDatabasePath = config('database.connections.sqlite.database');

        $this->assertNotSame(
            $this->normalizePath($primaryDatabasePath),
            $this->normalizePath($configuredDatabasePath),
        );
        $this->assertStringContainsString('/database/testing/', str_replace('\\', '/', $configuredDatabasePath));
        $this->assertSame('sqlite', config('database.default'));
    }

    public function test_suite_path_guard_handles_missing_sqlite_files_safely(): void
    {
        $missingPrimaryPath = base_path('database/missing-primary.sqlite');
        $missingTestPath = base_path('database/testing/missing-test.sqlite');

        $this->assertNotSame(
            $this->normalizePath($missingPrimaryPath),
            $this->normalizePath($missingTestPath),
        );
    }
}
