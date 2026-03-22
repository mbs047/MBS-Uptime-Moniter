<?php

namespace App\Filament\Resources\PlatformSettings;

use App\Filament\Resources\PlatformSettings\Pages\CreatePlatformSetting;
use App\Filament\Resources\PlatformSettings\Pages\EditPlatformSetting;
use App\Filament\Resources\PlatformSettings\Pages\ListPlatformSettings;
use App\Filament\Resources\PlatformSettings\Pages\ViewPlatformSetting;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformSettingResource extends Resource
{
    protected static ?string $model = PlatformSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $recordTitleAttribute = 'brand_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('brand_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('brand_tagline')
                    ->maxLength(255),
                TextInput::make('brand_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('support_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('mail_from_name')
                    ->maxLength(255),
                TextInput::make('mail_from_address')
                    ->email()
                    ->maxLength(255),
                TextInput::make('seo_title')
                    ->maxLength(255),
                Textarea::make('seo_description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('uptime_window_days')
                    ->numeric()
                    ->required(),
                TextInput::make('raw_run_retention_days')
                    ->numeric()
                    ->required(),
                TextInput::make('default_failure_threshold')
                    ->numeric()
                    ->required(),
                TextInput::make('default_recovery_threshold')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('brand_name'),
                TextEntry::make('support_email'),
                TextEntry::make('uptime_window_days'),
                TextEntry::make('raw_run_retention_days'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('brand_name')
            ->columns([
                TextColumn::make('brand_name')
                    ->searchable(),
                TextColumn::make('uptime_window_days')
                    ->label('Window'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ListPlatformSettings::route('/'),
            'create' => CreatePlatformSetting::route('/create'),
            'view' => ViewPlatformSetting::route('/{record}'),
            'edit' => EditPlatformSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return PlatformSetting::query()->count() === 0;
    }
}
