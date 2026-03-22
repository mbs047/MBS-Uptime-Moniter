<?php

namespace App\Services\RemoteIntegrations;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncStatus;
use App\Models\RemoteIntegration;
use App\Support\Http\RemoteIntegrationTlsOptions;
use Illuminate\Support\Facades\Http;
use Throwable;

class RemoteIntegrationSyncService
{
    public function __construct(
        protected readonly RemoteMetadataImporter $importer,
    ) {}

    public function sync(RemoteIntegration $integration): RemoteIntegration
    {
        try {
            $requestOptions = RemoteIntegrationTlsOptions::for($integration);

            $response = Http::timeout(10)
                ->acceptJson()
                ->when(
                    $requestOptions !== [],
                    fn ($request) => $request->withOptions($requestOptions),
                )
                ->when(
                    $integration->auth_mode === RemoteIntegrationAuthMode::Bearer && filled($integration->auth_secret),
                    fn ($request) => $request->withToken((string) $integration->auth_secret),
                )
                ->get($integration->metadata_url ?: rtrim($integration->base_url, '/').'/status/metadata')
                ->throw();

            return $this->importer->import($integration, $response->json());
        } catch (Throwable $exception) {
            $integration->forceFill([
                'last_sync_status' => RemoteIntegrationSyncStatus::Failed,
                'last_sync_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }
}
