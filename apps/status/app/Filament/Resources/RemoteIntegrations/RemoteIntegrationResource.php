<?php

namespace App\Filament\Resources\RemoteIntegrations;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use App\Enums\RemoteIntegrationSyncStatus;
use App\Filament\Resources\Concerns\PreventsDeletion;
use App\Filament\Resources\RemoteIntegrations\Pages\CreateRemoteIntegration;
use App\Filament\Resources\RemoteIntegrations\Pages\EditRemoteIntegration;
use App\Filament\Resources\RemoteIntegrations\Pages\ListRemoteIntegrations;
use App\Filament\Resources\RemoteIntegrations\Pages\ViewRemoteIntegration;
use App\Models\RemoteIntegration;
use App\Services\RemoteIntegrations\RemoteIntegrationSyncService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class RemoteIntegrationResource extends Resource
{
    use PreventsDeletion;

    protected static ?string $model = RemoteIntegration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Application')
                        ->description('Start with the Laravel app you want this monitor to own and sync.')
                        ->icon(Heroicon::OutlinedSquares2x2)
                        ->schema([
                            Section::make('Remote application')
                                ->schema([
                                    TextInput::make('name')
                                        ->maxLength(255)
                                        ->helperText('Optional. The sync process usually replaces this with the service name from the remote metadata payload.'),
                                    TextInput::make('base_url')
                                        ->required()
                                        ->url()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->helperText('Use the remote app base URL, for example https://billing.example.com.')
                                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $normalized = rtrim($state, '/');

                                            $set('base_url', $normalized);

                                            if (blank($get('name')) && $guessedName = static::guessNameFromUrl($normalized)) {
                                                $set('name', $guessedName);
                                            }

                                            if (blank($get('metadata_url'))) {
                                                $set('metadata_url', $normalized.'/status/metadata');
                                            }

                                            if (blank($get('health_url'))) {
                                                $set('health_url', $normalized.'/status/health');
                                            }
                                        }),
                                    Select::make('sync_mode')
                                        ->options(static::getSyncModeOptions())
                                        ->default(RemoteIntegrationSyncMode::Hybrid->value)
                                        ->required()
                                        ->helperText('Hybrid is recommended: let the remote app register itself, then allow the monitor to pull fresh metadata later.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                    Step::make('Endpoints & auth')
                        ->description('Define how the monitor should reach the remote metadata and health endpoints securely.')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->schema([
                            Section::make('Remote endpoints')
                                ->schema([
                                    TextInput::make('metadata_url')
                                        ->url()
                                        ->maxLength(255)
                                        ->helperText('Leave this blank to use {base_url}/status/metadata, or override it if the package route path changed.'),
                                    TextInput::make('health_url')
                                        ->url()
                                        ->maxLength(255)
                                        ->helperText('Leave this blank to use {base_url}/status/health, or override it for custom package paths.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                            Section::make('Authentication')
                                ->schema([
                                    Select::make('auth_mode')
                                        ->options(static::getAuthModeOptions())
                                        ->default(RemoteIntegrationAuthMode::Bearer->value)
                                        ->required()
                                        ->helperText('Bearer is the default for package-protected probe endpoints.'),
                                    TextInput::make('auth_secret')
                                        ->password()
                                        ->revealable()
                                        ->helperText('Use the remote STATUS_PROBE_TOKEN here. Leave it blank only if the remote metadata and health endpoints are intentionally public.')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                            Section::make('TLS')
                                ->schema([
                                    Toggle::make('tls_verify')
                                        ->label('Verify TLS certificates')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Keep this enabled in production. Turn it off only for local or self-signed HTTPS endpoints such as *.test.'),
                                    TextInput::make('tls_ca_path')
                                        ->label('Custom CA bundle path')
                                        ->maxLength(255)
                                        ->placeholder('/path/to/local-ca.pem')
                                        ->visible(fn (Get $get): bool => (bool) $get('tls_verify'))
                                        ->helperText('Optional. Prefer a local CA bundle when you have one, so TLS verification can stay enabled.')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                    Step::make('Review')
                        ->description('Check linked records and latest sync state before relying on the generated checks.')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->schema([
                            Section::make('Monitor-side state')
                                ->schema([
                                    TextInput::make('remote_app_id')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Filled from the remote metadata payload after the first successful sync.'),
                                    TextInput::make('service.name')
                                        ->label('Linked service')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('This local service receives the generated components and package-managed checks.'),
                                    TextInput::make('last_sync_status')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Use Sync now any time you change remote package configuration or credentials.'),
                                    TextInput::make('last_synced_at')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Textarea::make('last_sync_error')
                                        ->rows(4)
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpanFull()
                                        ->helperText('If sync fails, the previous linked service and checks stay in place until you fix the connection and sync again.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                ])
                    ->persistStepInQueryString('integration-step')
                    ->skippable(),
            ])->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Remote integration summary')
                    ->description('Review the remote endpoints, linked local service, and latest sync status before changing credentials or resyncing.')
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('Pending first sync'),
                        TextEntry::make('base_url')
                            ->copyable(),
                        TextEntry::make('metadata_url')
                            ->copyable(),
                        TextEntry::make('health_url')
                            ->copyable(),
                        TextEntry::make('sync_mode')
                            ->badge()
                            ->formatStateUsing(fn (?RemoteIntegrationSyncMode $state): ?string => $state?->label()),
                        TextEntry::make('auth_mode')
                            ->badge()
                            ->formatStateUsing(fn (?RemoteIntegrationAuthMode $state): ?string => $state?->label()),
                        TextEntry::make('tls_verify')
                            ->label('TLS verification')
                            ->badge()
                            ->formatStateUsing(fn (?bool $state): string => $state === false ? 'Disabled' : 'Enabled')
                            ->color(fn (?bool $state): string => $state === false ? 'warning' : 'success'),
                        TextEntry::make('tls_ca_path')
                            ->label('Custom CA bundle')
                            ->placeholder('Using the system CA store'),
                        TextEntry::make('service.name')
                            ->label('Linked service')
                            ->placeholder('Will be created on first sync'),
                        TextEntry::make('remote_app_id')
                            ->placeholder('Not synced yet'),
                        TextEntry::make('last_sync_status')
                            ->badge()
                            ->formatStateUsing(fn (?RemoteIntegrationSyncStatus $state): ?string => $state?->label()),
                        TextEntry::make('last_synced_at')
                            ->since()
                            ->placeholder('Never synced'),
                        TextEntry::make('last_sync_error')
                            ->columnSpanFull()
                            ->placeholder('No sync errors recorded'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->placeholder('Pending sync'),
                TextColumn::make('base_url')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('sync_mode')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncMode $state): ?string => $state?->label()),
                TextColumn::make('last_sync_status')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncStatus $state): ?string => $state?->label()),
                TextColumn::make('service.name')
                    ->label('Linked service')
                    ->placeholder('Not linked yet'),
                TextColumn::make('last_synced_at')
                    ->since()
                    ->placeholder('Never synced'),
            ])
            ->recordActions([
                ActionGroup::make([
                    static::makeSyncNowAction(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisHorizontal)
                    ->button(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRemoteIntegrations::route('/'),
            'create' => CreateRemoteIntegration::route('/create'),
            'view' => ViewRemoteIntegration::route('/{record}'),
            'edit' => EditRemoteIntegration::route('/{record}/edit'),
        ];
    }

    public static function makeSyncNowAction(): Action
    {
        return Action::make('sync_now')
            ->label('Sync now')
            ->icon(Heroicon::OutlinedArrowPath)
            ->action(function (RemoteIntegration $record): void {
                try {
                    app(RemoteIntegrationSyncService::class)->sync($record);

                    Notification::make()
                        ->title('Remote integration synced.')
                        ->body('The linked service, remote components, and package-managed checks were refreshed from the metadata payload.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Remote integration sync failed.')
                        ->body(static::describeSyncFailure($exception))
                        ->danger()
                        ->send();
                }
            });
    }

    public static function describeSyncFailure(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (! static::looksLikeTlsFailure($message)) {
            return $message;
        }

        return 'TLS connect error: For local or self-signed HTTPS endpoints such as *.test, open Endpoints & auth -> TLS and either disable Verify TLS certificates or set a Custom CA bundle path.';
    }

    /**
     * @return array<string, string>
     */
    protected static function getSyncModeOptions(): array
    {
        return collect(RemoteIntegrationSyncMode::cases())
            ->mapWithKeys(fn (RemoteIntegrationSyncMode $mode) => [$mode->value => $mode->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function getAuthModeOptions(): array
    {
        return collect(RemoteIntegrationAuthMode::cases())
            ->mapWithKeys(fn (RemoteIntegrationAuthMode $mode) => [$mode->value => $mode->label()])
            ->all();
    }

    protected static function guessNameFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || blank($host)) {
            return null;
        }

        return Str::of($host)
            ->replace(['www.', '.'], [' ', ' '])
            ->headline()
            ->value();
    }

    protected static function looksLikeTlsFailure(string $message): bool
    {
        return Str::contains(Str::lower($message), [
            'tls connect error',
            'ssl certificate problem',
            'self-signed certificate',
            'certificate verify failed',
            'curl error 35',
            'curl error 60',
        ]);
    }
}
