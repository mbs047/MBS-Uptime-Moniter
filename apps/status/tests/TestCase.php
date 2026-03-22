<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected static ?string $dedicatedTestDatabasePath = null;

    public function createApplication()
    {
        $this->configureDedicatedTestDatabase();

        return parent::createApplication();
    }

    protected function migrateDatabases()
    {
        $this->artisan('migrate', ['--force' => true]);
    }

    protected function configureDedicatedTestDatabase(): void
    {
        if (($this->environmentValue('APP_ENV') ?? 'testing') !== 'testing') {
            return;
        }

        $databasePath = $this->resolveDedicatedTestDatabasePath();
        $primaryDatabasePath = $this->primaryDatabasePath();

        if (realpath($databasePath) === realpath($primaryDatabasePath)) {
            throw new RuntimeException('Tests cannot run against database/database.sqlite. A dedicated test database is required.');
        }

        $this->ensureDirectoryExists(dirname($databasePath));
        $this->ensureFileExists($databasePath);

        $this->writeEnvironmentValue('DB_CONNECTION', 'sqlite');
        $this->writeEnvironmentValue('DB_DATABASE', $databasePath);
        $this->writeEnvironmentValue('DB_URL', '');
    }

    protected function resolveDedicatedTestDatabasePath(): string
    {
        if (static::$dedicatedTestDatabasePath !== null) {
            return static::$dedicatedTestDatabasePath;
        }

        $basePath = dirname(__DIR__);
        $directory = $this->environmentValue('TEST_DATABASE_DIRECTORY') ?: 'database/testing';
        $directory = str_starts_with($directory, '/')
            ? $directory
            : $basePath.'/'.$directory;

        static::$dedicatedTestDatabasePath = rtrim($directory, '/').'/phpunit-'.getmypid().'.sqlite';

        return static::$dedicatedTestDatabasePath;
    }

    protected function primaryDatabasePath(): string
    {
        return dirname(__DIR__).'/database/database.sqlite';
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        mkdir($directory, 0777, true);
    }

    protected function ensureFileExists(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        touch($path);
    }

    protected function environmentValue(string $key): ?string
    {
        return $_ENV[$key]
            ?? $_SERVER[$key]
            ?? (getenv($key) !== false ? getenv($key) : null);
    }

    protected function writeEnvironmentValue(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
}
