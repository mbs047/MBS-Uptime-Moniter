<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Services\RemoteIntegrations\RemoteIntegrationSyncService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRemoteIntegration extends EditRecord
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['auth_secret'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['base_url'] = rtrim((string) $data['base_url'], '/');
        $data['metadata_url'] = filled($data['metadata_url'] ?? null)
            ? $data['metadata_url']
            : $data['base_url'].'/status/metadata';
        $data['health_url'] = filled($data['health_url'] ?? null)
            ? $data['health_url']
            : $data['base_url'].'/status/health';

        if (blank($data['auth_secret'] ?? null)) {
            $data['auth_secret'] = $this->record->auth_secret;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        try {
            app(RemoteIntegrationSyncService::class)->sync($this->record);

            Notification::make()
                ->title('Remote integration saved and synced.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Remote integration saved, but sync failed.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
