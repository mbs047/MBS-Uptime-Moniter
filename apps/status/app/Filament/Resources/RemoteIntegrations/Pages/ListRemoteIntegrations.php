<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRemoteIntegrations extends ListRecords
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
