<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Enums\RemoteIntegrationAuthMode;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Services\RemoteIntegrations\RemoteIntegrationSyncService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRemoteIntegration extends CreateRecord
{
    protected static string $resource = RemoteIntegrationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['base_url'] = rtrim((string) $data['base_url'], '/');
        $data['metadata_url'] = filled($data['metadata_url'] ?? null)
            ? $data['metadata_url']
            : $data['base_url'].'/status/metadata';
        $data['health_url'] = filled($data['health_url'] ?? null)
            ? $data['health_url']
            : $data['base_url'].'/status/health';
        $data['auth_mode'] ??= RemoteIntegrationAuthMode::Bearer->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (blank($this->record->auth_secret)) {
            Notification::make()
                ->title('Remote integration created.')
                ->body('Add a bearer token when the remote app requires authentication, then sync.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(RemoteIntegrationSyncService::class)->sync($this->record);

            Notification::make()
                ->title('Remote integration created and synced.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Remote integration created, but sync failed.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
