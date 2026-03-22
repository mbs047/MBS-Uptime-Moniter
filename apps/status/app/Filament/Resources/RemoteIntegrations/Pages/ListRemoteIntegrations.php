<?php

namespace App\Filament\Resources\RemoteIntegrations\Pages;

use App\Filament\Pages\ConnectLaravelAppsGuide;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRemoteIntegrations extends ListRecords
{
    protected static string $resource = RemoteIntegrationResource::class;

    protected ?string $subheading = 'Link package-enabled Laravel apps, sync their metadata, and let the monitor generate the connected services, components, and checks.';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                CreateAction::make()
                    ->label('New remote integration')
                    ->icon(Heroicon::OutlinedGlobeAlt),
                Action::make('connection_guide')
                    ->label('Connection guide')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->color('gray')
                    ->url(ConnectLaravelAppsGuide::getUrl(panel: 'admin')),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button(),
        ];
    }
}
