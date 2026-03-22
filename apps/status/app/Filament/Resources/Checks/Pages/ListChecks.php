<?php

namespace App\Filament\Resources\Checks\Pages;

use App\Filament\Pages\ConnectLaravelAppsGuide;
use App\Filament\Resources\Checks\CheckResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListChecks extends ListRecords
{
    protected static string $resource = CheckResource::class;

    protected ?string $subheading = 'Create clear, threshold-based checks that explain why a service is degraded and work cleanly with shared Laravel probe payloads.';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                CreateAction::make()
                    ->label('New check')
                    ->icon(Heroicon::OutlinedBolt),
                Action::make('laravel_probe_guide')
                    ->label('Laravel package guide')
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
