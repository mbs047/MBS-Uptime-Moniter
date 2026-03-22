<?php

namespace App\Filament\Resources\AdminInvites;

use App\Filament\Resources\AdminInvites\Pages\CreateAdminInvite;
use App\Filament\Resources\AdminInvites\Pages\EditAdminInvite;
use App\Filament\Resources\AdminInvites\Pages\ListAdminInvites;
use App\Filament\Resources\AdminInvites\Pages\ViewAdminInvite;
use App\Models\AdminInvite;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminInviteResource extends Resource
{
    protected static ?string $model = AdminInvite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Access';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                DateTimePicker::make('expires_at'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('expires_at')
                    ->dateTime(),
                TextEntry::make('accepted_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('accepted_at')
                    ->since(),
                TextColumn::make('expires_at')
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
            'index' => ListAdminInvites::route('/'),
            'create' => CreateAdminInvite::route('/create'),
            'view' => ViewAdminInvite::route('/{record}'),
            'edit' => EditAdminInvite::route('/{record}/edit'),
        ];
    }
}
