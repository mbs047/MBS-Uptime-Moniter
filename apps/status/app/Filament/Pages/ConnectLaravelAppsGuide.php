<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ConnectLaravelAppsGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'guides/connect-laravel-apps';

    protected static ?string $title = 'Connect Laravel Apps';

    protected string $view = 'filament.pages.connect-laravel-apps-guide';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?string $subheading = 'Install the probe package in another Laravel application, then register or sync it from this monitor with copy-ready commands and expected next steps.';

    protected function getHeaderActions(): array
    {
        $settings = PlatformSetting::query()->first();

        return [
            Action::make('create_remote_integration')
                ->label('Create remote integration')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->url(RemoteIntegrationResource::getUrl('create')),
            Action::make('monitor_settings')
                ->label($settings ? 'Review monitor settings' : 'Create monitor settings')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->url($settings
                    ? PlatformSettingResource::getUrl('edit', ['record' => $settings])
                    : PlatformSettingResource::getUrl('create')),
            Action::make('package_repository')
                ->label('Open package repository')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url('https://github.com/mbs047/MBS-Uptime-Moniter-Package', shouldOpenInNewTab: true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $settings = PlatformSetting::current();
        $monitorUrl = $this->getMonitorUrl();
        $pushToken = $settings->probe_registration_token;
        $placeholderToken = filled($pushToken) ? $pushToken : 'configure-this-in-monitor-settings';

        return [
            'monitorUrl' => $monitorUrl,
            'registrationEndpoint' => $monitorUrl.'/api/integrations/probes/register',
            'packageName' => 'mbs047/laravel-status-probe',
            'packageRepositoryUrl' => 'https://github.com/mbs047/MBS-Uptime-Moniter-Package',
            'integrationCreateUrl' => RemoteIntegrationResource::getUrl('create'),
            'settingsUrl' => $this->getSettingsUrl(),
            'hasPushToken' => filled($pushToken),
            'pushToken' => $pushToken,
            'installCommand' => "composer require mbs047/laravel-status-probe\nphp artisan status-probe:install",
            'pushEnvSnippet' => implode(PHP_EOL, [
                'STATUS_MONITOR_URL='.$monitorUrl,
                'STATUS_MONITOR_TOKEN='.$placeholderToken,
            ]),
            'pullMetadataCurl' => implode(" \\\n", [
                'curl',
                '  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN"',
                '  https://your-app.example.com/status/metadata',
            ]),
            'pullHealthCurl' => implode(" \\\n", [
                'curl',
                '  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN"',
                '  https://your-app.example.com/status/health',
            ]),
            'pushRegisterCommand' => 'php artisan status-probe:register',
            'generatedArtifacts' => [
                'One linked remote integration record for the monitored application.',
                'One local service for the remote app, refreshed from the metadata payload.',
                'One component per package contributor such as app, db, cache, queue, or scheduler.',
                'One HTTP check per component, all pointed at the shared remote health endpoint with the correct JSON status path.',
            ],
        ];
    }

    private function getMonitorUrl(): string
    {
        $configured = config('app.url');

        if (filled($configured)) {
            return rtrim((string) $configured, '/');
        }

        return rtrim(url('/'), '/');
    }

    private function getSettingsUrl(): string
    {
        if ($settings = PlatformSetting::query()->first()) {
            return PlatformSettingResource::getUrl('edit', ['record' => $settings]);
        }

        return PlatformSettingResource::getUrl('create');
    }
}
