<?php

namespace App\Filament\Resources\Checks;

use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Filament\Resources\Checks\Pages\CreateCheck;
use App\Filament\Resources\Checks\Pages\EditCheck;
use App\Filament\Resources\Checks\Pages\ListChecks;
use App\Filament\Resources\Checks\Pages\ViewCheck;
use App\Jobs\RunCheckJob;
use App\Models\Check;
use App\Models\Component;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CheckResource extends Resource
{
    protected static ?string $model = Check::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $defaults = PlatformSetting::current();

        return $schema
            ->components([
                Wizard::make([
                    Step::make('Scope')
                        ->description('Choose what you are checking and how it should appear to operators.')
                        ->icon(Heroicon::OutlinedSquares2x2)
                        ->schema([
                            Section::make('Check identity')
                                ->schema([
                                    Select::make('component_id')
                                        ->relationship('component', 'display_name')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->required()
                                        ->helperText('Attach the check to the public component whose status should change when this probe fails.')
                                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                            if (filled($get('name'))) {
                                                return;
                                            }

                                            if ($suggested = static::suggestCheckName($state, $get('type'))) {
                                                $set('name', $suggested);
                                            }
                                        }),
                                    Select::make('type')
                                        ->options(static::getTypeOptions())
                                        ->required()
                                        ->live()
                                        ->helperText('Use HTTP for URLs, SSL for certificate expiry, DNS for records, and TCP for host:port reachability.')
                                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                            if (filled($get('name'))) {
                                                return;
                                            }

                                            if ($suggested = static::suggestCheckName($get('component_id'), $state)) {
                                                $set('name', $suggested);
                                            }
                                        }),
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->helperText('A clear internal label helps the dashboard explain why a component is unhealthy.'),
                                    Toggle::make('enabled')
                                        ->default(true)
                                        ->helperText('Disable a check when you want to keep its configuration without scheduling new runs.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                    Step::make('Cadence')
                        ->description('Control how often the check runs and how quickly health should flip after failures.')
                        ->icon(Heroicon::OutlinedClock)
                        ->schema([
                            Section::make('Schedule and thresholds')
                                ->schema([
                                    TextInput::make('interval_minutes')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('One minute is typical for critical paths. Increase it for slower or lower-priority checks.'),
                                    TextInput::make('timeout_seconds')
                                        ->numeric()
                                        ->default(10)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('Treat this as the maximum tolerated wait for the network operation itself.'),
                                    TextInput::make('failure_threshold')
                                        ->numeric()
                                        ->default($defaults->default_failure_threshold)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('The component changes status only after this many consecutive failed runs.'),
                                    TextInput::make('recovery_threshold')
                                        ->numeric()
                                        ->default($defaults->default_recovery_threshold)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('Use a higher recovery threshold only when you want multiple clean runs before clearing an issue.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                    Step::make('Connection')
                        ->description('Tell the monitor exactly what host, URL, or endpoint it needs to call.')
                        ->icon(Heroicon::OutlinedLink)
                        ->schema([
                            Section::make('HTTP request')
                                ->visible(fn (Get $get): bool => $get('type') === CheckType::Http->value)
                                ->schema([
                                    Select::make('config.method')
                                        ->options([
                                            'GET' => 'GET',
                                            'POST' => 'POST',
                                            'PUT' => 'PUT',
                                            'PATCH' => 'PATCH',
                                            'DELETE' => 'DELETE',
                                        ])
                                        ->default('GET')
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Http->value),
                                    TextInput::make('config.url')
                                        ->url()
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Http->value)
                                        ->helperText('Use the fully qualified remote URL, such as the package health endpoint in another Laravel app.')
                                        ->columnSpanFull(),
                                    Select::make('config.auth_type')
                                        ->options([
                                            'basic' => 'Basic auth',
                                            'bearer' => 'Bearer token',
                                        ])
                                        ->helperText('Use bearer auth for package-based Laravel probe endpoints protected by STATUS_PROBE_TOKEN.'),
                                    TextInput::make('secret_config.username')
                                        ->helperText('Only required when the HTTP endpoint uses basic auth.')
                                        ->hidden(fn (Get $get): bool => $get('config.auth_type') !== 'basic'),
                                    TextInput::make('secret_config.password')
                                        ->password()
                                        ->revealable()
                                        ->helperText('Stored encrypted at rest. Leave blank during edits to keep the current secret.')
                                        ->hidden(fn (Get $get): bool => $get('config.auth_type') !== 'basic'),
                                    TextInput::make('secret_config.token')
                                        ->password()
                                        ->revealable()
                                        ->helperText('Paste the remote STATUS_PROBE_TOKEN or any bearer token required by the endpoint.')
                                        ->hidden(fn (Get $get): bool => $get('config.auth_type') !== 'bearer'),
                                    KeyValue::make('config.headers')
                                        ->helperText('Optional. Add only the headers the remote endpoint actually requires.')
                                        ->columnSpanFull(),
                                    Textarea::make('config.json_body')
                                        ->rows(4)
                                        ->helperText('Optional JSON payload for POST, PUT, or PATCH requests. Paste valid JSON only.')
                                        ->placeholder("{\n  \"ping\": true\n}")
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                            Section::make('SSL check')
                                ->visible(fn (Get $get): bool => $get('type') === CheckType::Ssl->value)
                                ->schema([
                                    TextInput::make('config.host')
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Ssl->value)
                                        ->helperText('Use the certificate hostname the monitor should open a TLS handshake against.'),
                                    TextInput::make('config.port')
                                        ->numeric()
                                        ->default(443)
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Ssl->value)
                                        ->helperText('443 is the normal HTTPS port unless the service terminates TLS elsewhere.'),
                                    TextInput::make('config.minimum_days_remaining')
                                        ->numeric()
                                        ->default(14)
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Ssl->value)
                                        ->helperText('The check degrades once the certificate drops below this many days remaining.'),
                                ])
                                ->columnSpanFull()
                                ->columns(3),
                            Section::make('DNS check')
                                ->visible(fn (Get $get): bool => $get('type') === CheckType::Dns->value)
                                ->schema([
                                    TextInput::make('config.host')
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Dns->value)
                                        ->helperText('Enter the hostname whose DNS records should be resolved.'),
                                    Select::make('config.record_type')
                                        ->options([
                                            'A' => 'A',
                                            'AAAA' => 'AAAA',
                                            'CNAME' => 'CNAME',
                                            'MX' => 'MX',
                                            'TXT' => 'TXT',
                                            'NS' => 'NS',
                                        ])
                                        ->default('A')
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Dns->value),
                                    TagsInput::make('config.expected_values')
                                        ->separator(',')
                                        ->helperText('Optional. Add one or more expected record values when resolution alone is not enough.')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                            Section::make('TCP check')
                                ->visible(fn (Get $get): bool => $get('type') === CheckType::Tcp->value)
                                ->schema([
                                    TextInput::make('config.host')
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Tcp->value)
                                        ->helperText('Use the remote host the monitor should attempt to reach over TCP.'),
                                    TextInput::make('config.port')
                                        ->numeric()
                                        ->required(fn (Get $get): bool => $get('type') === CheckType::Tcp->value)
                                        ->helperText('This is usually 443, 80, or another known service port.'),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                        ]),
                    Step::make('Expectations')
                        ->description('Define what success looks like, especially for shared probe payloads and latency-sensitive endpoints.')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->schema([
                            Section::make('HTTP assertions')
                                ->visible(fn (Get $get): bool => $get('type') === CheckType::Http->value)
                                ->schema([
                                    TagsInput::make('config.expected_statuses')
                                        ->separator(',')
                                        ->helperText('Use comma-separated status codes such as 200 or 200,201. Leave 200 for most health endpoints.'),
                                    TextInput::make('config.max_latency_ms')
                                        ->numeric()
                                        ->helperText('Optional. Fail softly when latency exceeds this ceiling.'),
                                    Textarea::make('config.text_contains')
                                        ->rows(2)
                                        ->helperText('Optional. Require a specific response fragment when the body should include a known marker.'),
                                    TextInput::make('config.status_json_path')
                                        ->helperText('Use a JSON path such as checks.db.status when one shared health response contains many component statuses.')
                                        ->columnSpanFull(),
                                    Repeater::make('config.json_assertions')
                                        ->schema([
                                            TextInput::make('path')
                                                ->required()
                                                ->helperText('Dot notation path inside the JSON response, such as data.ok.'),
                                            TextInput::make('expected')
                                                ->helperText('Optional expected value at that path.'),
                                        ])
                                        ->helperText('Add JSON assertions when you need to validate more than status codes alone.')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->columns(2),
                            Section::make('Operator notes')
                                ->schema([
                                    TextEntry::make('latest_severity')
                                        ->label('Latest observed severity')
                                        ->state(fn (?Check $record): ?string => $record?->latest_severity?->label())
                                        ->badge()
                                        ->placeholder('No runs yet'),
                                    TextEntry::make('latest_error_summary')
                                        ->label('Latest error summary')
                                        ->state(fn (?Check $record): ?string => $record?->latest_error_summary)
                                        ->placeholder('No error recorded yet')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->visible(fn (?Check $record): bool => $record !== null),
                        ]),
                ])
                    ->persistStepInQueryString('check-step')
                    ->skippable(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Check summary')
                    ->description('Review the component, check type, and latest observed health outcome before running or editing the probe.')
                    ->schema([
                        TextEntry::make('component.display_name'),
                        TextEntry::make('name'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (?CheckType $state): ?string => $state?->label()),
                        TextEntry::make('latest_severity')
                            ->badge()
                            ->formatStateUsing(fn (?ComponentStatus $state): ?string => $state?->label()),
                        TextEntry::make('latest_error_summary')
                            ->columnSpanFull()
                            ->placeholder('No error recorded'),
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
                TextColumn::make('component.display_name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?CheckType $state): ?string => $state?->label()),
                TextColumn::make('latest_severity')
                    ->badge()
                    ->formatStateUsing(fn (?ComponentStatus $state): ?string => $state?->label()),
                TextColumn::make('next_run_at')
                    ->since()
                    ->placeholder('Will schedule after first save'),
                IconColumn::make('enabled')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('run_now')
                    ->label('Run now')
                    ->icon(Heroicon::OutlinedPlay)
                    ->action(function (Check $record): void {
                        RunCheckJob::dispatch($record->id, true);

                        Notification::make()
                            ->title('Check queued for immediate execution.')
                            ->body('Refresh the record in a moment to review the newest run result and severity.')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecks::route('/'),
            'create' => CreateCheck::route('/create'),
            'view' => ViewCheck::route('/{record}'),
            'edit' => EditCheck::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getTypeOptions(): array
    {
        return collect(CheckType::cases())
            ->mapWithKeys(fn (CheckType $type) => [$type->value => $type->label()])
            ->all();
    }

    protected static function suggestCheckName(?string $componentId, ?string $type): ?string
    {
        if (blank($componentId) || blank($type)) {
            return null;
        }

        $component = Component::query()->find($componentId);
        $typeLabel = CheckType::tryFrom((string) $type)?->label();

        if (! $component || blank($typeLabel)) {
            return null;
        }

        return sprintf('%s %s check', $component->display_name, $typeLabel);
    }
}
