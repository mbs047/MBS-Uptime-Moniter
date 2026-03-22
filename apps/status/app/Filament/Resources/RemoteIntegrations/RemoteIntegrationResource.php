<?php

namespace App\Filament\Resources\RemoteIntegrations;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use App\Enums\RemoteIntegrationSyncStatus;
use App\Filament\Resources\RemoteIntegrations\Pages\CreateRemoteIntegration;
use App\Filament\Resources\RemoteIntegrations\Pages\EditRemoteIntegration;
use App\Filament\Resources\RemoteIntegrations\Pages\ListRemoteIntegrations;
use App\Filament\Resources\RemoteIntegrations\Pages\ViewRemoteIntegration;
use App\Models\RemoteIntegration;
use App\Services\RemoteIntegrations\RemoteIntegrationSyncService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RemoteIntegrationResource extends Resource
{
    protected static ?string $model = RemoteIntegration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->helperText('Usually filled from the remote metadata payload after sync.'),
                TextInput::make('base_url')
                    ->required()
                    ->url()
                    ->maxLength(255),
                TextInput::make('metadata_url')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Defaults to {base_url}/status/metadata when left blank.'),
                TextInput::make('health_url')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Defaults to {base_url}/status/health when left blank.'),
                Select::make('sync_mode')
                    ->options(collect(RemoteIntegrationSyncMode::cases())->mapWithKeys(
                        fn (RemoteIntegrationSyncMode $mode) => [$mode->value => $mode->label()]
                    )->all())
                    ->default(RemoteIntegrationSyncMode::Hybrid->value)
                    ->required(),
                Select::make('auth_mode')
                    ->options(collect(RemoteIntegrationAuthMode::cases())->mapWithKeys(
                        fn (RemoteIntegrationAuthMode $mode) => [$mode->value => $mode->label()]
                    )->all())
                    ->default(RemoteIntegrationAuthMode::Bearer->value)
                    ->required(),
                TextInput::make('auth_secret')
                    ->password()
                    ->revealable()
                    ->helperText('Bearer token the monitor uses to call the remote health and metadata endpoints.'),
                TextInput::make('remote_app_id')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('service.name')
                    ->label('Linked service')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('last_sync_status')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('last_synced_at')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('last_sync_error')
                    ->rows(3)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('base_url'),
                TextEntry::make('sync_mode')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncMode $state) => $state?->label()),
                TextEntry::make('auth_mode')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationAuthMode $state) => $state?->label()),
                TextEntry::make('service.name')
                    ->label('Linked service'),
                TextEntry::make('remote_app_id'),
                TextEntry::make('last_sync_status')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncStatus $state) => $state?->label()),
                TextEntry::make('last_synced_at')
                    ->since(),
                TextEntry::make('last_sync_error')
                    ->columnSpanFull(),
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
                    ->searchable(),
                TextColumn::make('sync_mode')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncMode $state) => $state?->label()),
                TextColumn::make('last_sync_status')
                    ->badge()
                    ->formatStateUsing(fn (?RemoteIntegrationSyncStatus $state) => $state?->label()),
                TextColumn::make('service.name')
                    ->label('Linked service'),
                TextColumn::make('last_synced_at')
                    ->since(),
            ])
            ->recordActions([
                Action::make('sync_now')
                    ->label('Sync now')
                    ->action(function (RemoteIntegration $record): void {
                        try {
                            app(RemoteIntegrationSyncService::class)->sync($record);

                            Notification::make()
                                ->title('Remote integration synced.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Remote integration sync failed.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
}
