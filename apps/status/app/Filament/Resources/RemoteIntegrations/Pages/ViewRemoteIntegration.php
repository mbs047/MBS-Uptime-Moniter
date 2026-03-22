<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRemoteIntegration extends ViewRecord
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected ?string $subheading = 'Confirm the linked service, remote endpoints, and sync history before relying on the imported checks.';

    protected function getHeaderActions(): array
    {
        return [
            RemoteIntegrationResource::makeSyncNowAction(),
            EditAction::make(),
        ];
    }
}
