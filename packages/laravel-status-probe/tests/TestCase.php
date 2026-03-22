<?php

namespace Mbs047\LaravelStatusProbe\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Mbs047\LaravelStatusProbe\StatusProbeServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        $this->setProbeEnvironment('STATUS_PROBE_APP_ID', 'probe-app');
        $this->setProbeEnvironment('STATUS_PROBE_SERVICE_NAME', 'Probe App');
        $this->setProbeEnvironment('STATUS_PROBE_SERVICE_SLUG', 'probe-app');
        $this->setProbeEnvironment('STATUS_PROBE_SERVICE_DESCRIPTION', 'Package-driven health metadata.');
        $this->setProbeEnvironment('STATUS_PROBE_HEALTH_PATH', 'status/health');
        $this->setProbeEnvironment('STATUS_PROBE_METADATA_PATH', 'status/metadata');
        $this->setProbeEnvironment('STATUS_PROBE_TOKEN', 'probe-secret');

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            StatusProbeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.url', 'https://probe.example.com');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('status-probe.app_id', env('STATUS_PROBE_APP_ID', 'probe-app'));
        $app['config']->set('status-probe.service_name', env('STATUS_PROBE_SERVICE_NAME', 'Probe App'));
        $app['config']->set('status-probe.service_slug', env('STATUS_PROBE_SERVICE_SLUG', 'probe-app'));
        $app['config']->set('status-probe.service_description', env('STATUS_PROBE_SERVICE_DESCRIPTION', 'Package-driven health metadata.'));
        $app['config']->set('status-probe.health_path', env('STATUS_PROBE_HEALTH_PATH', 'status/health'));
        $app['config']->set('status-probe.metadata_path', env('STATUS_PROBE_METADATA_PATH', 'status/metadata'));
        $app['config']->set('status-probe.auth.mode', 'bearer');
        $app['config']->set('status-probe.auth.token', env('STATUS_PROBE_TOKEN', 'probe-secret'));
    }

    protected function refreshProbeApplication(): void
    {
        $this->refreshApplication();
    }

    protected function setProbeEnvironment(string $key, string $value): void
    {
        putenv(sprintf('%s=%s', $key, $value));
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
