<?php

$basePath = dirname(__DIR__);
$primaryDatabasePath = $basePath.'/database/database.sqlite';
$testDatabaseDirectory = getenv('TEST_DATABASE_DIRECTORY') ?: $basePath.'/database/testing';

$normalizePath = static function (string $path): string {
    $resolvedPath = realpath($path);

    if ($resolvedPath !== false) {
        return str_replace('\\', '/', $resolvedPath);
    }

    $path = str_replace('\\', '/', $path);

    if (! str_starts_with($path, '/')) {
        $path = getcwd().'/'.ltrim($path, '/');
    }

    return rtrim(preg_replace('#/+#', '/', $path) ?: $path, '/');
};

if (! str_starts_with($testDatabaseDirectory, '/')) {
    $testDatabaseDirectory = $basePath.'/'.ltrim($testDatabaseDirectory, '/');
}

if (! is_dir($testDatabaseDirectory)) {
    mkdir($testDatabaseDirectory, 0777, true);
}

$testDatabaseToken = getenv('TEST_TOKEN') ?: getmypid();
$testDatabasePath = rtrim($testDatabaseDirectory, '/')."/phpunit-{$testDatabaseToken}.sqlite";

if ($normalizePath($testDatabasePath) === $normalizePath($primaryDatabasePath)) {
    throw new RuntimeException('Tests cannot bootstrap against database/database.sqlite. A dedicated test database is required.');
}

if (! file_exists($testDatabasePath)) {
    touch($testDatabasePath);
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$testDatabasePath);
putenv('DB_URL=');
putenv('TEST_DATABASE_DIRECTORY='.$testDatabaseDirectory);

$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $testDatabasePath;
$_SERVER['DB_DATABASE'] = $testDatabasePath;
$_ENV['DB_URL'] = '';
$_SERVER['DB_URL'] = '';
$_ENV['TEST_DATABASE_DIRECTORY'] = $testDatabaseDirectory;
$_SERVER['TEST_DATABASE_DIRECTORY'] = $testDatabaseDirectory;

require $basePath.'/vendor/autoload.php';
