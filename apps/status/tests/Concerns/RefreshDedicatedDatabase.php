<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

trait RefreshDedicatedDatabase
{
    use RefreshDatabase {
        refreshDatabase as laravelRefreshDatabase;
    }

    public function refreshDatabase()
    {
        $this->configureDedicatedTestingDatabaseConnection($this->app ?? null);
        $this->assertDedicatedTestingDatabaseConfiguration();

        $this->laravelRefreshDatabase();
    }

    protected function beforeRefreshingDatabase()
    {
        $this->assertDedicatedTestingDatabaseConfiguration();
    }

    protected function migrateDatabases()
    {
        $this->assertDedicatedTestingDatabaseConfiguration();

        $this->artisan('migrate', ['--force' => true]);
    }

    protected function configureDedicatedTestingDatabaseConnection(?Application $app = null): void
    {
        if (($this->testingEnvironmentValue('APP_ENV') ?? 'testing') !== 'testing') {
            return;
        }

        $databasePath = $this->resolveDedicatedTestingDatabasePath();
        $primaryDatabasePath = $this->primaryDatabasePath();

        if (realpath($databasePath) === realpath($primaryDatabasePath)) {
            throw new RuntimeException('Tests cannot run against database/database.sqlite. A dedicated test database is required.');
        }

        $this->ensureDirectoryExists(dirname($databasePath));
        $this->ensureFileExists($databasePath);

        $this->writeTestingEnvironmentValue('DB_CONNECTION', 'sqlite');
        $this->writeTestingEnvironmentValue('DB_DATABASE', $databasePath);
        $this->writeTestingEnvironmentValue('DB_URL', '');

        if (! $app) {
            return;
        }

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.url', null);
        $app['config']->set('database.connections.sqlite.database', $databasePath);

        if ($app->bound('db')) {
            $app['db']->purge('sqlite');
            $app['db']->purge();
        }
    }

    protected function assertDedicatedTestingDatabaseConfiguration(): void
    {
        $configuredDatabasePath = $this->app['config']->get('database.connections.sqlite.database');

        if (! is_string($configuredDatabasePath)) {
            throw new RuntimeException('Tests must use the sqlite connection while running in the testing environment.');
        }

        if (realpath($configuredDatabasePath) === realpath($this->primaryDatabasePath())) {
            throw new RuntimeException('Tests cannot refresh the working application database. PHPUnit must use a dedicated sqlite file under database/testing.');
        }
    }

    protected function resolveDedicatedTestingDatabasePath(): string
    {
        $basePath = dirname(__DIR__, 2);
        $directory = $this->testingEnvironmentValue('TEST_DATABASE_DIRECTORY') ?: 'database/testing';
        $directory = str_starts_with($directory, '/')
            ? $directory
            : $basePath.'/'.$directory;

        $token = $this->testingEnvironmentValue('TEST_TOKEN') ?: getmypid();

        return rtrim($directory, '/')."/phpunit-{$token}.sqlite";
    }

    protected function primaryDatabasePath(): string
    {
        return dirname(__DIR__, 2).'/database/database.sqlite';
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

    protected function testingEnvironmentValue(string $key): ?string
    {
        return $_ENV[$key]
            ?? $_SERVER[$key]
            ?? (getenv($key) !== false ? getenv($key) : null);
    }

    protected function writeTestingEnvironmentValue(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
}
