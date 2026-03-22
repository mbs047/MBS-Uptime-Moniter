<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRemoteIntegration extends ViewRecord
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
