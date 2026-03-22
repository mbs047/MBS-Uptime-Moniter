<?php

namespace App\Filament\Resources\Incidents;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Resources\Incidents\Pages\ViewIncident;
use App\Models\Incident;
use App\Support\Filament\FormDefaults;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incident summary')
                    ->description('Incidents are manual operator events. They control public messaging, maintenance windows, and subscriber notifications.')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText('Write the plain-language issue title you want the public timeline to display.')
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                if (blank($state) || filled($get('slug'))) {
                                    return;
                                }

                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Used in the public incident URL. Keep it short and stable.'),
                        Select::make('status')
                            ->options(static::getStatusOptions())
                            ->default(IncidentStatus::Draft->value)
                            ->required()
                            ->helperText('Draft stays internal. Published is visible to subscribers and the public site. Resolved closes the incident timeline.'),
                        Select::make('severity')
                            ->options(static::getSeverityOptions())
                            ->default(FormDefaults::incidentSeverity())
                            ->required()
                            ->helperText('This severity overrides automated health for the affected services and components while the incident is active. New incidents start at degraded so operators can escalate intentionally when needed.'),
                        Textarea::make('summary')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Use a clear operational summary that explains what users are experiencing right now.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Affected scope')
                    ->description('Target services for broad incidents or specific components when the issue is limited to one part of a service.')
                    ->schema([
                        Select::make('services')
                            ->relationship('services', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Service-level incidents cascade to every public component inside that service.'),
                        Select::make('components')
                            ->relationship('components', 'display_name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Use component-level targeting when you want narrower impact without escalating the whole service.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Timing and publishing')
                    ->description('Use the actual start time for impact, scheduled windows for maintenance, and published or resolved timestamps to control the public timeline.')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->default(fn () => now())
                            ->helperText('When the customer-facing impact or maintenance actually began. New incidents start at the current time to speed up incident creation.'),
                        DateTimePicker::make('scheduled_starts_at')
                            ->helperText('Optional. Planned maintenance window start.'),
                        DateTimePicker::make('scheduled_ends_at')
                            ->helperText('Optional. Planned maintenance window end.'),
                        DateTimePicker::make('published_at')
                            ->helperText('Set automatically on publish if your workflow handles it, or enter it manually when recreating past incidents.'),
                        DateTimePicker::make('resolved_at')
                            ->helperText('Set this when the issue is fully resolved and public updates should stop.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Timeline updates')
                    ->description('Add timeline posts in the same order you want operators and subscribers to read them.')
                    ->schema([
                        Repeater::make('updates')
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->maxLength(255)
                                    ->helperText('Optional short label for the update, such as Investigating or Monitoring.'),
                                Textarea::make('body')
                                    ->required()
                                    ->rows(3)
                                    ->helperText('This is the main update body shown on the incident detail page and in notifications.'),
                                Select::make('status')
                                    ->options(static::getStatusOptions())
                                    ->helperText('Use published updates for live incident posts and resolved when closing out the timeline.'),
                                DateTimePicker::make('published_at')
                                    ->helperText('Leave blank until the update is ready to be visible externally.'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incident summary')
                    ->description('Review the public incident state, severity, and summary before updating the timeline or publishing changes.')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (?IncidentStatus $state): ?string => $state?->label()),
                        TextEntry::make('severity')
                            ->badge()
                            ->formatStateUsing(fn (?IncidentSeverity $state): ?string => $state?->label()),
                        TextEntry::make('summary')
                            ->columnSpanFull()
                            ->placeholder('No summary provided'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?IncidentStatus $state): ?string => $state?->label()),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (?IncidentSeverity $state): ?string => $state?->label()),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->placeholder('Not published'),
                TextColumn::make('resolved_at')
                    ->since()
                    ->placeholder('Still active'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
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
            'index' => ListIncidents::route('/'),
            'create' => CreateIncident::route('/create'),
            'view' => ViewIncident::route('/{record}'),
            'edit' => EditIncident::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getStatusOptions(): array
    {
        return collect(IncidentStatus::cases())
            ->mapWithKeys(fn (IncidentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function getSeverityOptions(): array
    {
        return collect(IncidentSeverity::cases())
            ->mapWithKeys(fn (IncidentSeverity $severity) => [$severity->value => $severity->label()])
            ->all();
    }
}
