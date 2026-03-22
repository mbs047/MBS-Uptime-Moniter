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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
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
        return $schema
            ->components([
                Select::make('component_id')
                    ->relationship('component', 'display_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(collect(CheckType::cases())->mapWithKeys(fn (CheckType $type) => [$type->value => $type->label()])->all())
                    ->required()
                    ->live(),
                Toggle::make('enabled')
                    ->default(true),
                TextInput::make('interval_minutes')
                    ->numeric()
                    ->default(1)
                    ->required(),
                TextInput::make('timeout_seconds')
                    ->numeric()
                    ->default(10)
                    ->required(),
                TextInput::make('failure_threshold')
                    ->numeric()
                    ->default(2)
                    ->required(),
                TextInput::make('recovery_threshold')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Select::make('config.method')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ])
                    ->default('GET')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                TextInput::make('config.url')
                    ->url()
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                KeyValue::make('config.headers')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                TagsInput::make('config.expected_statuses')
                    ->separator(',')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                TextInput::make('config.max_latency_ms')
                    ->numeric()
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                Textarea::make('config.text_contains')
                    ->rows(2)
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                Textarea::make('config.json_body')
                    ->rows(4)
                    ->helperText('Optional JSON payload for non-GET requests.')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                Select::make('config.auth_type')
                    ->options([
                        'basic' => 'Basic auth',
                        'bearer' => 'Bearer token',
                    ])
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value),
                TextInput::make('secret_config.username')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value || $get('config.auth_type') !== 'basic'),
                TextInput::make('secret_config.password')
                    ->password()
                    ->revealable()
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value || $get('config.auth_type') !== 'basic'),
                TextInput::make('secret_config.token')
                    ->password()
                    ->revealable()
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value || $get('config.auth_type') !== 'bearer'),
                Repeater::make('config.json_assertions')
                    ->schema([
                        TextInput::make('path')->required(),
                        TextInput::make('expected'),
                    ])
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Http->value)
                    ->columnSpanFull(),
                TextInput::make('config.host')
                    ->hidden(fn (Get $get) => ! in_array($get('type'), [CheckType::Ssl->value, CheckType::Dns->value, CheckType::Tcp->value], true)),
                TextInput::make('config.port')
                    ->numeric()
                    ->hidden(fn (Get $get) => ! in_array($get('type'), [CheckType::Ssl->value, CheckType::Tcp->value], true)),
                TextInput::make('config.minimum_days_remaining')
                    ->numeric()
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Ssl->value),
                Select::make('config.record_type')
                    ->options([
                        'A' => 'A',
                        'AAAA' => 'AAAA',
                        'CNAME' => 'CNAME',
                        'MX' => 'MX',
                        'TXT' => 'TXT',
                        'NS' => 'NS',
                    ])
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Dns->value),
                TagsInput::make('config.expected_values')
                    ->separator(',')
                    ->hidden(fn (Get $get) => $get('type') !== CheckType::Dns->value),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('component.display_name'),
                TextEntry::make('name'),
                TextEntry::make('type'),
                TextEntry::make('latest_severity')
                    ->badge(),
                TextEntry::make('latest_error_summary')
                    ->columnSpanFull(),
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
                    ->badge(),
                TextColumn::make('latest_severity')
                    ->badge()
                    ->formatStateUsing(fn (?ComponentStatus $state) => $state?->label()),
                TextColumn::make('next_run_at')
                    ->since(),
                IconColumn::make('enabled')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('run_now')
                    ->label('Run now')
                    ->action(fn (Check $record) => RunCheckJob::dispatch($record->id, true)),
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
}
