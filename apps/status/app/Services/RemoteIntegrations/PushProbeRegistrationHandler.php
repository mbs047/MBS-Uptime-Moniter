<?php

namespace App\Services\RemoteIntegrations;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use App\Enums\RemoteIntegrationSyncStatus;
use App\Models\RemoteIntegration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PushProbeRegistrationHandler
{
    public function __construct(
        protected readonly RemoteMetadataImporter $importer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): RemoteIntegration
    {
        $validated = Validator::make($payload, [
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
            'auth.secret' => ['nullable', 'string'],
            'components' => ['required', 'array', 'min:1'],
        ])->validate();

        $integration = RemoteIntegration::query()
            ->firstOrNew(['remote_app_id' => $validated['app_id']]);

        try {
            return DB::transaction(function () use ($integration, $validated): RemoteIntegration {
                $integration->fill([
                    'name' => $validated['service']['name'],
                    'remote_app_id' => $validated['app_id'],
                    'base_url' => rtrim($validated['endpoints']['base_url'], '/'),
                    'health_url' => $validated['endpoints']['health_url'],
                    'metadata_url' => $validated['endpoints']['metadata_url'],
                    'auth_mode' => RemoteIntegrationAuthMode::from($validated['auth']['mode']),
                    'sync_mode' => $integration->exists
                        ? ($integration->sync_mode ?? RemoteIntegrationSyncMode::Hybrid)
                        : RemoteIntegrationSyncMode::Hybrid,
                    'last_registration_at' => now(),
                ]);

                if (filled($validated['auth']['secret'] ?? null)) {
                    $integration->auth_secret = $validated['auth']['secret'];
                }

                $integration->save();

                return $this->importer->import($integration, $validated);
            });
        } catch (Throwable $exception) {
            $integration->forceFill([
                'last_sync_status' => RemoteIntegrationSyncStatus::Failed,
                'last_sync_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }
}
