<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

    protected ?string $subheading = 'Guide operators through package install, connection method selection, and monitor-side sync with clearer steps and copy-ready values.';

    protected function getHeaderActions(): array
    {
        $settings = PlatformSetting::query()->first();

        return [
            ActionGroup::make([
                Action::make('create_remote_integration')
                    ->label('Create remote integration')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(RemoteIntegrationResource::getUrl('create')),
                Action::make('monitor_settings')
                    ->label($settings ? 'Review monitor settings' : 'Create monitor settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url($settings
                        ? PlatformSettingResource::getUrl('edit', ['record' => $settings])
                        : PlatformSettingResource::getUrl('create')),
                Action::make('package_repository')
                    ->label('Open package repository')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url('https://github.com/mbs047/MBS-Uptime-Moniter-Package', shouldOpenInNewTab: true),
            ])
                ->label('Guide actions')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->button()
                ->color('warning')
                ->dropdownPlacement('bottom-end'),
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
            'packagePackagistUrl' => 'https://packagist.org/packages/mbs047/laravel-status-probe',
            'packageRepositoryUrl' => 'https://github.com/mbs047/MBS-Uptime-Moniter-Package',
            'integrationCreateUrl' => RemoteIntegrationResource::getUrl('create'),
            'settingsUrl' => $this->getSettingsUrl(),
            'hasPushToken' => filled($pushToken),
            'pushToken' => $pushToken,
            'packageRequireCommand' => 'composer require mbs047/laravel-status-probe',
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
            'flowSteps' => [
                [
                    'title' => 'Install the package in the remote app',
                    'body' => 'Run the package install command, keep the probe token private, and make sure APP_URL points to the real application URL.',
                    'badge' => 'Step 1',
                ],
                [
                    'title' => 'Choose pull, push, or a hybrid setup',
                    'body' => 'Pull is easiest for operator-led onboarding. Push is faster when the remote app already knows this monitor URL. Hybrid gives you both.',
                    'badge' => 'Step 2',
                ],
                [
                    'title' => 'Review the generated service and checks',
                    'body' => 'After the first sync, confirm the linked service, generated components, and shared-health checks before relying on the public page.',
                    'badge' => 'Step 3',
                ],
            ],
            'installOutcomes' => [
                'Authenticated /status/health and /status/metadata endpoints.',
                'Built-in contributors for application, database, and cache health.',
                'Optional queue and scheduler heartbeats for deeper operational coverage.',
            ],
            'criticalRemoteValues' => [
                'APP_URL must be correct or the monitor will import broken URLs.',
                'STATUS_PROBE_TOKEN protects the package endpoints by default.',
                'Custom health and metadata paths are supported and reflected in the metadata payload.',
            ],
            'pullSteps' => [
                'Create a remote integration from this admin panel.',
                'Enter the remote app base URL and the remote probe bearer token.',
                'Save and let the monitor sync immediately, or run Sync now later.',
                'Review the generated service, components, and checks before publishing incidents.',
            ],
            'troubleshooting' => [
                'If sync succeeds but checks stay unhealthy, test the remote health endpoint separately from the metadata endpoint.',
                'If the remote app changed STATUS_PROBE_HEALTH_PATH or metadata path, re-sync so the monitor picks up the new URLs.',
                'If queue or scheduler health looks stale, confirm the remote app is running its worker and scheduler heartbeat on a shared cache store.',
            ],
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
