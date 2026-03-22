<?php

namespace App\Filament\Resources\AdminInvites;

use App\Filament\Resources\AdminInvites\Pages\CreateAdminInvite;
use App\Filament\Resources\AdminInvites\Pages\EditAdminInvite;
use App\Filament\Resources\AdminInvites\Pages\ListAdminInvites;
use App\Filament\Resources\AdminInvites\Pages\ViewAdminInvite;
use App\Mail\AdminInviteMail;
use App\Models\AdminInvite;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

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
                Section::make('Invitee')
                    ->description('Send an invite when a teammate needs access but should choose their own password.')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->helperText('Optional. Pre-fills the name on the acceptance screen.'),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->helperText('The invitation email will be sent here.'),
                    ])
                    ->columns(2),
                Section::make('Access window')
                    ->description('Give the recipient enough time to accept the invite without leaving stale links active forever.')
                    ->schema([
                        DateTimePicker::make('expires_at')
                            ->default(now()->addDays(7))
                            ->helperText('Leave this in place for a one-week default, or shorten it for higher-sensitivity access.')
                            ->seconds(false),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->placeholder('Not provided'),
                TextEntry::make('email')
                    ->copyable(),
                TextEntry::make('token')
                    ->label('Invite URL')
                    ->formatStateUsing(fn (string $state): string => route('admin.invites.show', $state))
                    ->copyable(),
                TextEntry::make('status')
                    ->state(fn (AdminInvite $record): string => static::getInviteStatusLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => static::getInviteStatusColor($state)),
                TextEntry::make('creator.email')
                    ->label('Created by')
                    ->placeholder('Initial setup'),
                TextEntry::make('expires_at')
                    ->dateTime()
                    ->placeholder('Does not expire'),
                TextEntry::make('accepted_at')
                    ->dateTime()
                    ->placeholder('Pending acceptance'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->placeholder('No preset name'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->state(fn (AdminInvite $record): string => static::getInviteStatusLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => static::getInviteStatusColor($state)),
                TextColumn::make('creator.email')
                    ->label('Created by')
                    ->placeholder('Initial setup'),
                TextColumn::make('accepted_at')
                    ->since()
                    ->placeholder('Pending'),
                TextColumn::make('expires_at')
                    ->since()
                    ->placeholder('No expiry set'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                static::makeResendInviteAction(),
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

    public static function makeResendInviteAction(): Action
    {
        return Action::make('resend_invite')
            ->label(fn (AdminInvite $record): string => static::getInviteStatusLabel($record) === 'Expired' ? 'Extend and resend' : 'Resend invite')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('gray')
            ->hidden(fn (AdminInvite $record): bool => filled($record->accepted_at))
            ->action(function (AdminInvite $record): void {
                if (filled($record->expires_at) && $record->expires_at->isPast()) {
                    $record->forceFill([
                        'expires_at' => now()->addDays(7),
                    ])->save();
                }

                Mail::to($record->email)->queue(new AdminInviteMail($record));

                Notification::make()
                    ->title('Admin invite queued for delivery.')
                    ->body('The latest invite link is ready and the recipient email has been queued again.')
                    ->success()
                    ->send();
            });
    }

    public static function getInviteStatusLabel(AdminInvite $record): string
    {
        if (filled($record->accepted_at)) {
            return 'Accepted';
        }

        if (filled($record->expires_at) && $record->expires_at->isPast()) {
            return 'Expired';
        }

        return 'Pending';
    }

    public static function getInviteStatusColor(string $state): string
    {
        return match ($state) {
            'Accepted' => 'success',
            'Expired' => 'danger',
            default => 'warning',
        };
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
