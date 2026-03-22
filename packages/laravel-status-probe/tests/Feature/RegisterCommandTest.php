<?php

namespace Mbs047\LaravelStatusProbe\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class RegisterCommandTest extends TestCase
{
    public function test_register_command_posts_to_the_monitor(): void
    {
        config([
            'status-probe.monitor.url' => 'https://uptime.example.test',
            'status-probe.monitor.token' => 'monitor-secret',
            'status-probe.monitor.timeout_seconds' => 15,
        ]);

        Http::fake([
            'https://uptime.example.test/api/integrations/probes/register' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('status-probe:register')
            ->expectsOutput('Status probe registration pushed successfully.')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://uptime.example.test/api/integrations/probes/register'
                && $request->hasHeader('Authorization', 'Bearer monitor-secret')
                && $request['app_id'] === 'probe-app';
        });
    }

    public function test_register_command_shows_local_tls_guidance_for_connection_failures(): void
    {
        config([
            'status-probe.monitor.url' => 'https://uptime.test',
            'status-probe.monitor.token' => 'monitor-secret',
        ]);

        Http::fake([
            'https://uptime.test/api/integrations/probes/register' => Http::failedConnection('TLS connect error'),
        ]);

        $this->artisan('status-probe:register')
            ->expectsOutput('Unable to connect to the status monitor.')
            ->expectsOutputToContain('TLS connect error')
            ->expectsOutputToContain('php artisan status-probe:register --insecure')
            ->expectsOutputToContain('STATUS_MONITOR_VERIFY=false')
            ->expectsOutputToContain('STATUS_MONITOR_CA_PATH=/path/to/local-ca.pem')
            ->assertFailed();
    }

    public function test_register_command_explains_monitor_token_failures(): void
    {
        config([
            'status-probe.monitor.url' => 'https://uptime.example.test',
            'status-probe.monitor.token' => 'wrong-token',
        ]);

        Http::fake([
            'https://uptime.example.test/api/integrations/probes/register' => Http::response([
                'message' => 'Probe registration token is invalid. Copy the current monitor token from Platform Settings and update STATUS_MONITOR_TOKEN.',
            ], 401),
        ]);

        $this->artisan('status-probe:register')
            ->expectsOutput('The status monitor rejected the registration request.')
            ->expectsOutputToContain('Probe registration token is invalid')
            ->expectsOutputToContain('STATUS_MONITOR_TOKEN must exactly match the monitor probe registration token in Platform Settings')
            ->assertFailed();
    }
}
