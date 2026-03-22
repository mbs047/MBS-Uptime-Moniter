<?php

namespace Mbs047\LaravelStatusProbe\Support;

use Illuminate\Support\Str;
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;

class MetadataPayloadFactory
{
    public function __construct(
        protected readonly ProbeManager $manager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function make(bool $includeSecret = false): array
    {
        $serviceName = (string) config('status-probe.service_name', config('app.name', 'Laravel Application'));

        return [
            'app_id' => config('status-probe.app_id'),
            'service' => [
                'name' => $serviceName,
                'slug' => config('status-probe.service_slug') ?: Str::slug($serviceName),
                'description' => config('status-probe.service_description'),
            ],
            'endpoints' => [
                'base_url' => rtrim((string) config('app.url'), '/'),
                'health_url' => url((string) config('status-probe.health_path')),
                'metadata_url' => url((string) config('status-probe.metadata_path')),
            ],
            'auth' => array_filter([
                'mode' => config('status-probe.auth.mode', 'bearer'),
                'secret' => $includeSecret ? config('status-probe.auth.token') : null,
            ], fn ($value) => $value !== null && $value !== ''),
            'components' => array_map(
                fn (ProbeContributor $contributor): array => [
                    'key' => $contributor->key(),
                    'label' => $contributor->label(),
                    'description' => $contributor->description(),
                    'status_json_path' => sprintf('checks.%s.status', $contributor->key()),
                    'check' => array_merge([
                        'type' => 'http',
                        'method' => 'GET',
                        'expected_statuses' => [200],
                        'interval_minutes' => config('status-probe.monitor.interval_minutes', 1),
                        'timeout_seconds' => config('status-probe.monitor.timeout_seconds', 10),
                        'failure_threshold' => config('status-probe.monitor.failure_threshold', 2),
                        'recovery_threshold' => config('status-probe.monitor.recovery_threshold', 1),
                    ], $contributor->defaultCheckConfig()),
                ],
                $this->manager->contributors(),
            ),
        ];
    }
}
