<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\RemoteIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProbeRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_registration_requires_the_configured_bearer_token(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'probe_registration_token' => 'monitor-secret',
        ]);

        $this->postJson('/api/integrations/probes/register', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'Probe registration token is missing. Copy the current monitor token from Platform Settings and set it as STATUS_MONITOR_TOKEN in the remote app.',
            );
    }

    public function test_probe_registration_reports_invalid_bearer_token_clearly(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'probe_registration_token' => 'monitor-secret',
        ]);

        $this->withToken('wrong-token')
            ->postJson('/api/integrations/probes/register', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'Probe registration token is invalid. Copy the current monitor token from Platform Settings and update STATUS_MONITOR_TOKEN in the remote app.',
            );
    }

    public function test_probe_registration_creates_or_updates_an_integration(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
            'probe_registration_token' => 'monitor-secret',
        ]);

        $this->withToken('monitor-secret')
            ->postJson('/api/integrations/probes/register', $this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'Probe registration synchronized.');

        $this->assertCount(1, RemoteIntegration::query()->get());

        $this->withToken('monitor-secret')
            ->postJson('/api/integrations/probes/register', array_replace_recursive($this->payload(), [
                'auth' => ['mode' => 'bearer', 'secret' => 'rotated-probe-token'],
            ]))
            ->assertOk();

        $this->assertCount(1, RemoteIntegration::query()->get());
        $this->assertSame('rotated-probe-token', RemoteIntegration::query()->firstOrFail()->auth_secret);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'app_id' => 'billing-api',
            'service' => [
                'name' => 'Billing API',
                'slug' => 'billing-api',
                'description' => 'Billing platform health',
            ],
            'endpoints' => [
                'base_url' => 'https://billing.example.com',
                'health_url' => 'https://billing.example.com/status/health',
                'metadata_url' => 'https://billing.example.com/status/metadata',
            ],
            'auth' => [
                'mode' => 'bearer',
                'secret' => 'probe-token',
            ],
            'components' => [
                [
                    'key' => 'app',
                    'label' => 'Application',
                    'description' => 'Runtime health',
                    'status_json_path' => 'checks.app.status',
                    'check' => [
                        'type' => 'http',
                        'method' => 'GET',
                        'expected_statuses' => [200],
                        'interval_minutes' => 1,
                        'timeout_seconds' => 10,
                        'failure_threshold' => 2,
                        'recovery_threshold' => 1,
                    ],
                ],
            ],
        ];
    }
}
