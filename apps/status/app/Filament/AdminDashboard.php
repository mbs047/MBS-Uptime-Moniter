<?php

namespace App\Filament;

use App\Filament\Pages\ConnectLaravelAppsGuide;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Models\PlatformSetting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class AdminDashboard extends Dashboard
{
    protected ?string $subheading = 'Review live health, then onboard monitored Laravel apps, publish incidents, and keep the public status page accurate.';

    public function getColumns(): int|array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }

    protected function getHeaderActions(): array
    {
        $settings = PlatformSetting::query()->first();

        return [
            ActionGroup::make([
                Action::make('connect_laravel_app')
                    ->label('Connect Laravel app')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->url(ConnectLaravelAppsGuide::getUrl(panel: 'admin')),
                Action::make('create_remote_integration')
                    ->label('New remote integration')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(RemoteIntegrationResource::getUrl('create')),
                Action::make('create_incident')
                    ->label('Publish incident')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('warning')
                    ->url(IncidentResource::getUrl('create')),
                Action::make('configure_monitor')
                    ->label($settings ? 'Review monitor settings' : 'Create monitor settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url($settings
                        ? PlatformSettingResource::getUrl('edit', ['record' => $settings])
                        : PlatformSettingResource::getUrl('create')),
            ])
                ->label('Quick actions')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->button()
                ->color('warning')
                ->dropdownPlacement('bottom-end'),
        ];
    }
}
