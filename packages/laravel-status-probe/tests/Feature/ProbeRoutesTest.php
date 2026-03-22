<?php

namespace Mbs047\LaravelStatusProbe\Tests\Feature;

use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class ProbeRoutesTest extends TestCase
{
    public function test_bearer_token_is_required_for_probe_routes(): void
    {
        $this->getJson('/status/health')->assertUnauthorized();
        $this->getJson('/status/metadata')->assertUnauthorized();
    }

    public function test_health_path_is_configurable(): void
    {
        $this->setProbeEnvironment('STATUS_PROBE_HEALTH_PATH', 'healthz');
        $this->refreshProbeApplication();

        $this->withToken('probe-secret')
            ->getJson('/healthz')
            ->assertOk()
            ->assertJsonPath('overall_status', 'operational');
    }

    public function test_metadata_path_is_configurable(): void
    {
        $this->setProbeEnvironment('STATUS_PROBE_METADATA_PATH', 'status/probe');
        $this->refreshProbeApplication();

        $this->withToken('probe-secret')
            ->getJson('/status/probe')
            ->assertOk()
            ->assertJsonPath('service.slug', 'probe-app');
    }

    public function test_service_name_and_slug_overrides_flow_into_metadata(): void
    {
        config([
            'status-probe.service_name' => 'Billing App',
            'status-probe.service_slug' => 'billing-app',
        ]);

        $this->withToken('probe-secret')
            ->getJson('/status/metadata')
            ->assertOk()
            ->assertJsonPath('service.name', 'Billing App')
            ->assertJsonPath('service.slug', 'billing-app');
    }

    public function test_custom_contributors_appear_in_health_and_metadata_payloads(): void
    {
        $this->app->singleton('custom.probe', fn () => new class implements ProbeContributor
        {
            public function key(): string
            {
                return 'search';
            }

            public function label(): string
            {
                return 'Search';
            }

            public function description(): ?string
            {
                return 'Custom contributor';
            }

            public function defaultCheckConfig(): array
            {
                return [];
            }

            public function resolve(): ProbeResult
            {
                return new ProbeResult(ProbeStatus::Degraded, 'Custom probe is degraded.');
            }
        });

        $this->app->tag('custom.probe', 'status-probe.contributors');

        $this->withToken('probe-secret')
            ->getJson('/status/health')
            ->assertOk()
            ->assertJsonPath('checks.search.status', 'degraded');

        $this->withToken('probe-secret')
            ->getJson('/status/metadata')
            ->assertOk()
            ->assertJsonPath('components.3.key', 'search');
    }
}
