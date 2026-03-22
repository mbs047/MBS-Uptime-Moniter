<?php

namespace App\Filament\Resources\Admins;

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Pages\ViewAdmin;
use App\Filament\Resources\Concerns\PreventsDeletion;
use App\Models\Admin;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    use PreventsDeletion;

    protected static ?string $model = Admin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Access';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Use direct admin creation sparingly. Invites are usually the safer default for teammates because they let each person choose their own password.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Shown across the admin panel, emails, and invite history.'),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('This is the sign-in address for the admin account.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Access')
                    ->description('Create a password only for direct access. Leave it blank while editing to keep the current password unchanged.')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->minLength(10)
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'Use at least 10 characters for direct admin access.'
                                : 'Enter a new password only when you want to rotate the current one.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->disabled(fn (?Admin $record): bool => $record?->isCurrentAdmin() ?? false)
                            ->helperText(fn (?Admin $record): string => $record?->isCurrentAdmin()
                                ? 'Your current signed-in admin stays active while you are working.'
                                : 'Disable access without deleting the record or losing audit history.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Admin account')
                    ->description('Review the core access state, verification status, and login activity for this administrator.')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->copyable(),
                        IconEntry::make('is_active')
                            ->boolean(),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->placeholder('Not verified yet'),
                        TextEntry::make('last_login_at')
                            ->dateTime()
                            ->placeholder('No login recorded'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->state(fn (Admin $record): bool => filled($record->email_verified_at)),
                TextColumn::make('last_login_at')
                    ->since()
                    ->placeholder('Never'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisHorizontal)
                    ->button(),
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
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'view' => ViewAdmin::route('/{record}'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
