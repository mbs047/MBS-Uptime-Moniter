<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected static ?string $dedicatedTestDatabasePath = null;

    public function createApplication()
    {
        $this->configureDedicatedTestDatabase();

        $app = parent::createApplication();

        $this->configureDedicatedTestDatabase($app);

        return $app;
    }

    protected function migrateDatabases()
    {
        $this->artisan('migrate', ['--force' => true]);
    }

    protected function configureDedicatedTestDatabase(?Application $app = null): void
    {
        if (($this->environmentValue('APP_ENV') ?? 'testing') !== 'testing') {
            return;
        }

        $databasePath = $this->resolveDedicatedTestDatabasePath();
        $primaryDatabasePath = $this->primaryDatabasePath();

        if ($this->normalizePath($databasePath) === $this->normalizePath($primaryDatabasePath)) {
            throw new RuntimeException('Tests cannot run against database/database.sqlite. A dedicated test database is required.');
        }

        $this->ensureDirectoryExists(dirname($databasePath));
        $this->ensureFileExists($databasePath);

        $this->writeEnvironmentValue('DB_CONNECTION', 'sqlite');
        $this->writeEnvironmentValue('DB_DATABASE', $databasePath);
        $this->writeEnvironmentValue('DB_URL', '');

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

    protected function beforeRefreshingDatabase()
    {
        $configuredDatabasePath = $this->app['config']->get('database.connections.sqlite.database');

        if (! is_string($configuredDatabasePath)) {
            return;
        }

        if ($this->normalizePath($configuredDatabasePath) === $this->normalizePath($this->primaryDatabasePath())) {
            throw new RuntimeException('Tests cannot refresh the working application database. PHPUnit must use a dedicated sqlite file under database/testing.');
        }
    }

    protected function normalizePath(string $path): string
    {
        $resolvedPath = realpath($path);

        if ($resolvedPath !== false) {
            return str_replace('\\', '/', $resolvedPath);
        }

        $path = str_replace('\\', '/', $path);

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        return rtrim(preg_replace('#/+#', '/', $path) ?: $path, '/');
    }
}
