<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewRemoteIntegration extends ViewRecord
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected ?string $subheading = 'Confirm the linked service, remote endpoints, and sync history before relying on the imported checks.';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                RemoteIntegrationResource::makeSyncNowAction(),
                EditAction::make(),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button(),
        ];
    }
}
