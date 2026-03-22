<?php

namespace Tests\Feature;

use App\Enums\RemoteIntegrationSyncStatus;
use App\Models\Check;
use App\Models\Component;
use App\Models\PlatformSetting;
use App\Models\RemoteIntegration;
use App\Models\Service;
use App\Services\RemoteIntegrations\PushProbeRegistrationHandler;
use App\Services\RemoteIntegrations\RemoteIntegrationSyncService;
use App\Support\Http\RemoteIntegrationTlsOptions;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class RemoteIntegrationSyncTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_pull_sync_creates_service_components_and_checks_from_remote_metadata(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        $integration = RemoteIntegration::factory()->create([
            'name' => 'Billing API',
            'remote_app_id' => 'billing-api',
            'base_url' => 'https://billing.example.com',
            'health_url' => 'https://billing.example.com/status/health',
            'metadata_url' => 'https://billing.example.com/status/metadata',
            'auth_secret' => 'probe-token',
        ]);

        Http::fake([
            'https://billing.example.com/status/metadata' => Http::response($this->metadataPayload()),
        ]);

        $synced = app(RemoteIntegrationSyncService::class)->sync($integration);

        $service = Service::query()->firstOrFail();
        $component = Component::query()->where('remote_component_key', 'db')->firstOrFail();
        $check = Check::query()->where('remote_component_key', 'db')->firstOrFail();

        $this->assertSame($service->id, $synced->service_id);
        $this->assertSame('Billing API', $service->name);
        $this->assertSame('Database', $component->display_name);
        $this->assertSame('checks.db.status', $check->config['status_json_path']);
        $this->assertSame('probe-token', $check->secret_config['token']);
        $this->assertSame(RemoteIntegrationSyncStatus::Succeeded, $synced->last_sync_status);
    }

    public function test_push_registration_upserts_the_same_remote_integration_without_duplication(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        $handler = app(PushProbeRegistrationHandler::class);

        $first = $handler->handle(array_replace_recursive($this->metadataPayload(), [
            'auth' => [
                'mode' => 'bearer',
                'secret' => 'first-token',
            ],
        ]));

        $second = $handler->handle(array_replace_recursive($this->metadataPayload(), [
            'service' => [
                'name' => 'Billing API',
                'slug' => 'billing-api',
                'description' => 'Updated description',
            ],
            'auth' => [
                'mode' => 'bearer',
                'secret' => 'rotated-token',
            ],
        ]));

        $this->assertSame($first->id, $second->id);
        $this->assertCount(1, RemoteIntegration::query()->get());
        $this->assertSame('rotated-token', $second->fresh()->auth_secret);
        $this->assertCount(2, Component::query()->get());
        $this->assertCount(2, Check::query()->get());
    }

    public function test_sync_failures_preserve_existing_configuration_and_mark_the_sync_failed(): void
    {
        $integration = RemoteIntegration::factory()->create([
            'name' => 'Billing API',
            'remote_app_id' => 'billing-api',
            'metadata_url' => 'https://billing.example.com/status/metadata',
        ]);

        Http::fake([
            'https://billing.example.com/status/metadata' => Http::response(['error' => 'boom'], 500),
        ]);

        try {
            app(RemoteIntegrationSyncService::class)->sync($integration);
            $this->fail('Expected sync to throw.');
        } catch (\Throwable) {
            // Expected.
        }

        $integration->refresh();

        $this->assertSame('Billing API', $integration->name);
        $this->assertSame(RemoteIntegrationSyncStatus::Failed, $integration->last_sync_status);
        $this->assertNotNull($integration->last_sync_error);
    }

    public function test_remote_integrations_can_disable_tls_verification_for_local_https_targets(): void
    {
        $integration = RemoteIntegration::factory()->make([
            'tls_verify' => false,
            'tls_ca_path' => null,
        ]);

        $this->assertSame(
            ['verify' => false],
            RemoteIntegrationTlsOptions::for($integration),
        );
    }

    public function test_remote_integrations_can_use_a_custom_ca_bundle_path(): void
    {
        $integration = RemoteIntegration::factory()->make([
            'tls_verify' => true,
            'tls_ca_path' => '/tmp/local-ca.pem',
        ]);

        $this->assertSame(
            ['verify' => '/tmp/local-ca.pem'],
            RemoteIntegrationTlsOptions::for($integration),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadataPayload(): array
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
                [
                    'key' => 'db',
                    'label' => 'Database',
                    'description' => 'Database connectivity',
                    'status_json_path' => 'checks.db.status',
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
