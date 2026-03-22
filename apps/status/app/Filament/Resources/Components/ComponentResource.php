<?php

namespace App\Filament\Resources\Components;

use App\Enums\ComponentStatus;
use App\Filament\Resources\Components\Pages\CreateComponent;
use App\Filament\Resources\Components\Pages\EditComponent;
use App\Filament\Resources\Components\Pages\ListComponents;
use App\Filament\Resources\Components\Pages\ViewComponent;
use App\Filament\Resources\Concerns\PreventsDeletion;
use App\Models\Component;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComponentResource extends Resource
{
    use PreventsDeletion;

    protected static ?string $model = Component::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Component details')
                    ->description('Components are the public items inside a service, such as API workers, billing jobs, or email delivery.')
                    ->schema([
                        Select::make('service_id')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Choose the service this public component belongs to.'),
                        TextInput::make('display_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('This is the public label visitors will see on the status page.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Optional context explaining what the component does.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Display controls')
                    ->description('Use these values to manage public visibility and ordering without changing the component identity.')
                    ->schema([
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower values appear first within the service group.'),
                        Toggle::make('is_public')
                            ->default(true)
                            ->required()
                            ->helperText('Hidden components stay manageable in admin without appearing publicly.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Component summary')
                    ->description('Review the parent service, current public status, and descriptive context for this component.')
                    ->schema([
                        TextEntry::make('service.name'),
                        TextEntry::make('display_name'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('service.name')
                    ->searchable(),
                TextColumn::make('display_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?ComponentStatus $state) => $state?->label()),
                TextColumn::make('automated_status')
                    ->badge()
                    ->formatStateUsing(fn (?ComponentStatus $state) => $state?->label()),
                IconColumn::make('is_public')
                    ->boolean(),
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
            'index' => ListComponents::route('/'),
            'create' => CreateComponent::route('/create'),
            'view' => ViewComponent::route('/{record}'),
            'edit' => EditComponent::route('/{record}/edit'),
        ];
    }
}
