<?php

namespace App\Filament\Resources\Incidents;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Resources\Incidents\Pages\ViewIncident;
use App\Models\Incident;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(collect(IncidentStatus::cases())->mapWithKeys(fn (IncidentStatus $status) => [$status->value => $status->label()])->all())
                    ->default(IncidentStatus::Draft->value)
                    ->required(),
                Select::make('severity')
                    ->options(collect(IncidentSeverity::cases())->mapWithKeys(fn (IncidentSeverity $severity) => [$severity->value => $severity->label()])->all())
                    ->required(),
                Textarea::make('summary')
                    ->rows(4)
                    ->columnSpanFull(),
                Select::make('services')
                    ->relationship('services', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('components')
                    ->relationship('components', 'display_name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('scheduled_starts_at'),
                DateTimePicker::make('scheduled_ends_at'),
                DateTimePicker::make('published_at'),
                DateTimePicker::make('resolved_at'),
                Repeater::make('updates')
                    ->relationship()
                    ->schema([
                        TextInput::make('title')->maxLength(255),
                        Textarea::make('body')->required()->rows(3),
                        Select::make('status')
                            ->options(collect(IncidentStatus::cases())->mapWithKeys(fn (IncidentStatus $status) => [$status->value => $status->label()])->all()),
                        DateTimePicker::make('published_at'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('severity')
                    ->badge(),
                TextEntry::make('summary')
                    ->columnSpanFull(),
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
                    ->badge(),
                TextColumn::make('severity')
                    ->badge(),
                TextColumn::make('published_at')
                    ->dateTime(),
                TextColumn::make('resolved_at')
                    ->since(),
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
}
