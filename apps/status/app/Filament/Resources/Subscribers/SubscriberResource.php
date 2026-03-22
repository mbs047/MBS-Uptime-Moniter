<?php

namespace App\Filament\Resources\Subscribers;

use App\Filament\Resources\Subscribers\Pages\CreateSubscriber;
use App\Filament\Resources\Subscribers\Pages\EditSubscriber;
use App\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Filament\Resources\Subscribers\Pages\ViewSubscriber;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscriber details')
                    ->description('Subscribers receive incident emails only after they confirm their address and stay subscribed.')
                    ->schema([
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->helperText('Use the address that should receive published incident create, update, and resolve emails.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscriber summary')
                    ->description('Review whether this recipient is verified and still eligible to receive incident emails.')
                    ->schema([
                        TextEntry::make('email')
                            ->copyable(),
                        IconEntry::make('verified_at')
                            ->boolean()
                            ->state(fn (Subscriber $record) => filled($record->verified_at)),
                        TextEntry::make('unsubscribed_at')
                            ->dateTime()
                            ->placeholder('Still subscribed'),
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
                TextColumn::make('email')
                    ->searchable(),
                IconColumn::make('verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->state(fn (Subscriber $record) => filled($record->verified_at)),
                IconColumn::make('unsubscribed_at')
                    ->label('Unsubscribed')
                    ->boolean()
                    ->state(fn (Subscriber $record) => filled($record->unsubscribed_at)),
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
            'index' => ListSubscribers::route('/'),
            'create' => CreateSubscriber::route('/create'),
            'view' => ViewSubscriber::route('/{record}'),
            'edit' => EditSubscriber::route('/{record}/edit'),
        ];
    }
}
