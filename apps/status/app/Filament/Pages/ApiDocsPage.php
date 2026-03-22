<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use App\Models\PlatformSetting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnitEnum;

class ApiDocsPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracketSquare;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'guides/api-docs';

    protected static ?string $title = 'API Docs';

    protected string $view = 'filament.pages.api-docs-page';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected ?string $subheading = 'Review the public status API, inspect the private probe registration contract, and run safe live previews without leaving the admin panel.';

    public string $subscriberPreviewEmail = '';

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $endpointResponses = [];

    public function mount(): void
    {
        $this->subscriberPreviewEmail = sprintf(
            'docs-preview+%s@example.test',
            Str::lower(Str::random(6)),
        );
    }

    protected function getHeaderActions(): array
    {
        $settings = PlatformSetting::query()->first();

        return [
            ActionGroup::make([
                Action::make('open_status_page')
                    ->label('Open status page')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(url('/'), shouldOpenInNewTab: true),
                Action::make('connect_laravel_apps')
                    ->label('Connect Laravel apps guide')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->url(ConnectLaravelAppsGuide::getUrl(panel: 'admin')),
                Action::make('monitor_settings')
                    ->label($settings ? 'Review monitor settings' : 'Create monitor settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url($settings
                        ? PlatformSettingResource::getUrl('edit', ['record' => $settings])
                        : PlatformSettingResource::getUrl('create')),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->button()
                ->color('warning')
                ->dropdownPlacement('bottom-end'),
        ];
    }

    public function testEndpoint(string $key): void
    {
        $endpoint = collect($this->endpointCatalog())
            ->flatten(1)
            ->firstWhere('key', $key);

        abort_unless(is_array($endpoint), 404);

        $this->endpointResponses[$key] = $this->executeEndpointPreview($endpoint);

        $response = $this->endpointResponses[$key];

        $notification = Notification::make()
            ->title(sprintf('%s preview updated.', $endpoint['title']))
            ->body('Review the response panel below the endpoint card.');

        if (($response['ok'] ?? false) === true) {
            $notification->success()->send();

            return;
        }

        $notification->warning()->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $settings = PlatformSetting::current();

        return [
            'monitorUrl' => rtrim((string) config('app.url', url('/')), '/'),
            'hasProbeRegistrationToken' => filled($settings->probe_registration_token),
            'endpointCatalog' => $this->endpointCatalog(),
            'endpointResponses' => $this->endpointResponses,
            'relatedSubscriberRoutes' => [
                [
                    'method' => 'GET',
                    'path' => '/status/subscribers/confirm/{token}',
                    'summary' => 'Confirms a subscriber and redirects back to the public status page.',
                ],
                [
                    'method' => 'GET',
                    'path' => '/status/subscribers/unsubscribe/{token}',
                    'summary' => 'Marks a subscriber as unsubscribed and redirects back to the public status page.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function endpointCatalog(): array
    {
        $monitorUrl = rtrim((string) config('app.url', url('/')), '/');

        return [
            'public' => [
                [
                    'key' => 'summary',
                    'title' => 'Summary',
                    'method' => 'GET',
                    'path' => '/api/status/summary',
                    'url' => $monitorUrl.'/api/status/summary',
                    'visibility' => 'Public',
                    'auth' => 'No auth',
                    'description' => 'Returns the overall status banner data, generated timestamp, incident counts, uptime window, and brand metadata used by embeds or dashboards.',
                    'requestBody' => null,
                    'highlights' => [
                        'overall_status',
                        'generated_at',
                        'active_incident_count',
                        'brand.name / brand.tagline / brand.support_email',
                    ],
                    'previewMode' => 'read',
                ],
                [
                    'key' => 'services',
                    'title' => 'Services',
                    'method' => 'GET',
                    'path' => '/api/status/services',
                    'url' => $monitorUrl.'/api/status/services',
                    'visibility' => 'Public',
                    'auth' => 'No auth',
                    'description' => 'Returns all public services, nested components, current status, uptime percentage, 90-day bars, and active incident references.',
                    'requestBody' => null,
                    'highlights' => [
                        'service.name / service.status',
                        'components[].display_name',
                        'components[].uptime_90d_percent',
                        'components[].active_incidents[]',
                    ],
                    'previewMode' => 'read',
                ],
                [
                    'key' => 'incidents',
                    'title' => 'Incidents',
                    'method' => 'GET',
                    'path' => '/api/status/incidents',
                    'url' => $monitorUrl.'/api/status/incidents',
                    'visibility' => 'Public',
                    'auth' => 'No auth',
                    'description' => 'Returns published incidents ordered for public consumption, including active incidents first and recent resolved history after that.',
                    'requestBody' => null,
                    'highlights' => [
                        'slug / title / severity',
                        'published_at / resolved_at / started_at',
                        'affected_service_ids / affected_component_ids',
                        'latest_update',
                    ],
                    'previewMode' => 'read',
                ],
                [
                    'key' => 'subscribers',
                    'title' => 'Subscribers',
                    'method' => 'POST',
                    'path' => '/api/status/subscribers',
                    'url' => $monitorUrl.'/api/status/subscribers',
                    'visibility' => 'Public',
                    'auth' => 'No auth',
                    'description' => 'Starts or refreshes the email confirmation flow for a subscriber. The preview button runs safely without persisting the subscriber or queueing mail.',
                    'requestBody' => [
                        'email' => 'docs-preview@example.test',
                    ],
                    'highlights' => [
                        'Validates email:rfc',
                        'Returns a neutral confirmation message',
                        'Preview mode rolls back the subscriber record and fakes mail dispatch',
                    ],
                    'previewMode' => 'preview',
                ],
            ],
            'private' => [
                [
                    'key' => 'probe_registration',
                    'title' => 'Probe registration',
                    'method' => 'POST',
                    'path' => '/api/integrations/probes/register',
                    'url' => $monitorUrl.'/api/integrations/probes/register',
                    'visibility' => 'Private',
                    'auth' => 'Bearer token',
                    'description' => 'Accepts package registration payloads from Laravel apps using the status probe package. The preview button runs inside a database transaction and rolls everything back afterward.',
                    'requestBody' => $this->probeRegistrationPreviewPayload(),
                    'highlights' => [
                        'Requires the monitor-side probe registration bearer token',
                        'Upserts the remote integration, service, components, and generated checks',
                        'Preview mode rolls back database writes after the response is captured',
                    ],
                    'previewMode' => 'preview',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $endpoint
     * @return array<string, mixed>
     */
    protected function executeEndpointPreview(array $endpoint): array
    {
        $payload = match ($endpoint['key']) {
            'subscribers' => [
                'email' => Str::lower(trim($this->subscriberPreviewEmail)),
            ],
            default => $endpoint['requestBody'],
        };

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($endpoint['key'] === 'probe_registration' && filled(PlatformSetting::current()->probe_registration_token)) {
            $headers['Authorization'] = 'Bearer '.PlatformSetting::current()->probe_registration_token;
        }

        $previewNote = match ($endpoint['previewMode']) {
            'preview' => 'Safe preview only. Database changes were rolled back after the response was captured.',
            default => 'Live read-only request against the current application state.',
        };

        $execute = fn (): array => $this->dispatchInternalRequest(
            $endpoint['method'],
            $endpoint['path'],
            is_array($payload) ? $payload : [],
            $headers,
        );

        $result = match ($endpoint['previewMode']) {
            'preview' => $this->runPreviewTransaction($endpoint['key'], $execute),
            default => $execute(),
        };

        $result['tested_at'] = now()->toIso8601String();
        $result['preview_note'] = $previewNote;

        return $result;
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    protected function runPreviewTransaction(string $key, callable $callback): array
    {
        if ($key === 'subscribers') {
            $originalMailer = Mail::getFacadeRoot();
            Mail::fake();

            DB::beginTransaction();

            try {
                return $callback();
            } finally {
                DB::rollBack();
                Mail::swap($originalMailer);
            }
        }

        DB::beginTransaction();

        try {
            return $callback();
        } finally {
            DB::rollBack();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    protected function dispatchInternalRequest(string $method, string $path, array $payload, array $headers = []): array
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($name))] = $value;
        }

        $content = null;

        if ($method !== 'GET') {
            $server['CONTENT_TYPE'] = 'application/json';
            $content = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        $request = Request::create($path, $method, [], [], [], $server, $content);

        try {
            /** @var Response $response */
            $response = app()->handle($request);
        } catch (Throwable $exception) {
            $rendered = app(ExceptionHandler::class)->render($request, $exception);

            return [
                'status' => $rendered->getStatusCode(),
                'ok' => false,
                'content_type' => $rendered->headers->get('Content-Type'),
                'body' => $this->formatResponseBody($rendered),
                'exception' => sprintf('%s: %s', $exception::class, $exception->getMessage()),
            ];
        }

        app()->terminate($request, $response);

        return [
            'status' => $response->getStatusCode(),
            'ok' => $response->isSuccessful(),
            'content_type' => $response->headers->get('Content-Type'),
            'body' => $this->formatResponseBody($response),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function probeRegistrationPreviewPayload(): array
    {
        $remoteUrl = 'https://billing.example.com';

        return [
            'app_id' => 'docs-preview-app',
            'service' => [
                'name' => 'Billing API',
                'slug' => 'billing-api',
                'description' => 'Package-driven preview payload for the monitor API docs page.',
            ],
            'endpoints' => [
                'base_url' => $remoteUrl,
                'health_url' => $remoteUrl.'/status/health',
                'metadata_url' => $remoteUrl.'/status/metadata',
            ],
            'auth' => [
                'mode' => 'bearer',
                'secret' => 'probe-preview-token',
            ],
            'components' => [
                [
                    'key' => 'app',
                    'label' => 'Application',
                    'description' => 'Preview runtime health',
                    'status_json_path' => 'checks.app.status',
                    'check' => [
                        'type' => 'http',
                        'method' => 'GET',
                        'expected_statuses' => [200],
                        'interval_minutes' => 1,
                        'timeout_seconds' => 10,
                        'failure_threshold' => 2,
                        'recovery_threshold' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function formatResponseBody(Response $response): string
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return '{}';
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $content;
    }
}
