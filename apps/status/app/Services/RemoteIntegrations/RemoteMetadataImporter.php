<?php

namespace App\Services\RemoteIntegrations;

use App\Enums\RemoteIntegrationAuthMode;
use App\Models\Check;
use App\Models\Component;
use App\Models\PlatformSetting;
use App\Models\RemoteIntegration;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RemoteMetadataImporter
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(array $payload): array
    {
        return Validator::make($payload, [
            'app_id' => ['required', 'string', 'max:255'],
            'service' => ['required', 'array'],
            'service.name' => ['required', 'string', 'max:255'],
            'service.slug' => ['nullable', 'string', 'max:255'],
            'service.description' => ['nullable', 'string'],
            'endpoints' => ['required', 'array'],
            'endpoints.base_url' => ['required', 'url'],
            'endpoints.health_url' => ['required', 'url'],
            'endpoints.metadata_url' => ['required', 'url'],
            'auth' => ['required', 'array'],
            'auth.mode' => ['required', 'in:bearer'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.key' => ['required', 'string', 'max:255'],
            'components.*.label' => ['required', 'string', 'max:255'],
            'components.*.description' => ['nullable', 'string'],
            'components.*.status_json_path' => ['required', 'string', 'max:255'],
            'components.*.check' => ['required', 'array'],
            'components.*.check.type' => ['required', 'in:http'],
            'components.*.check.method' => ['nullable', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'components.*.check.expected_statuses' => ['nullable', 'array', 'min:1'],
            'components.*.check.expected_statuses.*' => ['integer', 'between:100,599'],
            'components.*.check.interval_minutes' => ['nullable', 'integer', 'min:1'],
            'components.*.check.timeout_seconds' => ['nullable', 'integer', 'min:1'],
            'components.*.check.failure_threshold' => ['nullable', 'integer', 'min:1'],
            'components.*.check.recovery_threshold' => ['nullable', 'integer', 'min:1'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(RemoteIntegration $integration, array $payload): RemoteIntegration
    {
        $validated = $this->validate($payload);
        $settings = PlatformSetting::current();

        return DB::transaction(function () use ($integration, $validated, $settings): RemoteIntegration {
            $integration->fill([
                'name' => $validated['service']['name'],
                'remote_app_id' => $validated['app_id'],
                'base_url' => $this->normalizeBaseUrl($validated['endpoints']['base_url']),
                'health_url' => $validated['endpoints']['health_url'],
                'metadata_url' => $validated['endpoints']['metadata_url'],
                'auth_mode' => RemoteIntegrationAuthMode::from($validated['auth']['mode']),
            ]);

            $service = $this->upsertService($integration, $validated);
            $integration->service()->associate($service);
            $integration->last_sync_status = 'succeeded';
            $integration->last_sync_error = null;
            $integration->last_synced_at = now();
            $integration->save();

            foreach ($validated['components'] as $index => $componentPayload) {
                $component = $this->upsertComponent($integration, $service, $componentPayload, $index);
                $this->upsertCheck($integration, $component, $componentPayload, $settings);
            }

            return $integration->fresh(['service']);
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function upsertService(RemoteIntegration $integration, array $validated): Service
    {
        $service = $integration->service ?? new Service([
            'sort_order' => Service::query()->max('sort_order') + 1,
            'is_public' => true,
        ]);

        $service->fill([
            'name' => $validated['service']['name'],
            'slug' => $this->uniqueServiceSlug(
                $validated['service']['slug'] ?: Str::slug($validated['service']['name']),
                $service->id,
            ),
            'description' => $validated['service']['description'] ?? null,
        ]);

        $service->save();

        return $service;
    }

    /**
     * @param  array<string, mixed>  $componentPayload
     */
    protected function upsertComponent(RemoteIntegration $integration, Service $service, array $componentPayload, int $index): Component
    {
        $component = Component::query()->firstOrNew([
            'remote_integration_id' => $integration->id,
            'remote_component_key' => $componentPayload['key'],
        ]);

        if (! $component->exists) {
            $component->sort_order = Component::query()
                ->where('service_id', $service->id)
                ->max('sort_order') + $index + 1;
            $component->is_public = true;
        }

        $component->fill([
            'service_id' => $service->id,
            'remote_integration_id' => $integration->id,
            'remote_component_key' => $componentPayload['key'],
            'display_name' => $componentPayload['label'],
            'description' => $componentPayload['description'] ?? null,
        ]);
        $component->save();

        return $component;
    }

    /**
     * @param  array<string, mixed>  $componentPayload
     */
    protected function upsertCheck(RemoteIntegration $integration, Component $component, array $componentPayload, PlatformSetting $settings): Check
    {
        $checkPayload = $componentPayload['check'];
        $check = Check::query()->firstOrNew([
            'remote_integration_id' => $integration->id,
            'remote_component_key' => $componentPayload['key'],
        ]);

        $check->fill([
            'component_id' => $component->id,
            'remote_integration_id' => $integration->id,
            'remote_component_key' => $componentPayload['key'],
            'name' => sprintf('%s status', $componentPayload['label']),
            'type' => 'http',
            'interval_minutes' => $checkPayload['interval_minutes'] ?? 1,
            'timeout_seconds' => $checkPayload['timeout_seconds'] ?? 10,
            'failure_threshold' => $checkPayload['failure_threshold'] ?? $settings->default_failure_threshold,
            'recovery_threshold' => $checkPayload['recovery_threshold'] ?? $settings->default_recovery_threshold,
            'enabled' => true,
            'config' => [
                'method' => $checkPayload['method'] ?? 'GET',
                'url' => $integration->health_url,
                'expected_statuses' => $checkPayload['expected_statuses'] ?? [200],
                'status_json_path' => $componentPayload['status_json_path'],
                'auth_type' => $integration->auth_mode?->value ?? RemoteIntegrationAuthMode::Bearer->value,
            ],
            'secret_config' => filled($integration->auth_secret)
                ? ['token' => $integration->auth_secret]
                : [],
        ]);
        $check->save();

        return $check;
    }

    protected function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/');
    }

    protected function uniqueServiceSlug(string $slug, ?int $ignoreServiceId = null): string
    {
        $candidate = Str::slug($slug) ?: 'remote-service';
        $suffix = 1;

        while (Service::query()
            ->where('slug', $candidate)
            ->when($ignoreServiceId, fn ($query) => $query->whereKeyNot($ignoreServiceId))
            ->exists()) {
            $suffix++;
            $candidate = Str::slug($slug).'-'.$suffix;
        }

        return $candidate;
    }
}
