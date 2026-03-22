<?php

namespace App\Filament\Resources\PlatformSettings;

use App\Filament\Resources\PlatformSettings\Pages\CreatePlatformSetting;
use App\Filament\Resources\PlatformSettings\Pages\EditPlatformSetting;
use App\Filament\Resources\PlatformSettings\Pages\ListPlatformSettings;
use App\Filament\Resources\PlatformSettings\Pages\ViewPlatformSetting;
use App\Models\PlatformSetting;
use App\Support\Filament\FormDefaults;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlatformSettingResource extends Resource
{
    protected static ?string $model = PlatformSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $recordTitleAttribute = 'brand_name';

    public static function form(Schema $schema): Schema
    {
        $defaults = FormDefaults::platformSettings();

        return $schema
            ->components([
                Section::make('Brand and public copy')
                    ->description('These values shape the public status page, browser metadata, and support messaging.')
                    ->schema([
                        TextInput::make('brand_name')
                            ->default($defaults['brand_name'])
                            ->required()
                            ->maxLength(255)
                            ->helperText('Displayed in the public status page header and the admin panel brand area. Starts with the platform default on first setup.'),
                        TextInput::make('brand_tagline')
                            ->default($defaults['brand_tagline'])
                            ->maxLength(255)
                            ->helperText('Short supporting line shown on the public status experience. Starts with the baseline monitor tagline on first setup.'),
                        TextInput::make('brand_url')
                            ->default($defaults['brand_url'])
                            ->url()
                            ->maxLength(255)
                            ->helperText('Where users should go when they click the brand from the public status page. Prefills from APP_URL when available.'),
                        TextInput::make('support_email')
                            ->default($defaults['support_email'])
                            ->email()
                            ->maxLength(255)
                            ->helperText('Public-facing support contact for status or incident questions. Prefills from the configured mail sender address when available.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Mail delivery')
                    ->description('These values are used when the monitor sends incident emails to subscribers or administrators.')
                    ->schema([
                        TextInput::make('mail_from_name')
                            ->default($defaults['mail_from_name'])
                            ->maxLength(255)
                            ->helperText('Friendly sender name for incident updates. Prefills from MAIL_FROM_NAME or the current brand name.'),
                        TextInput::make('mail_from_address')
                            ->default($defaults['mail_from_address'])
                            ->email()
                            ->maxLength(255)
                            ->helperText('Sender email address used for all outgoing status notifications. Prefills from MAIL_FROM_ADDRESS when available.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Monitoring defaults')
                    ->description('New checks start from these defaults unless an operator overrides them on the form.')
                    ->schema([
                        TextInput::make('uptime_window_days')
                            ->default($defaults['uptime_window_days'])
                            ->numeric()
                            ->required()
                            ->minValue(30)
                            ->helperText('Controls how many days of history the public status page shows for uptime bars and summary percentages. Starts at 90 days by default.'),
                        TextInput::make('raw_run_retention_days')
                            ->default($defaults['raw_run_retention_days'])
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Raw check runs older than this can be pruned after their daily aggregates are preserved. Starts at 14 days by default.'),
                        TextInput::make('default_failure_threshold')
                            ->default($defaults['default_failure_threshold'])
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('New checks inherit this many consecutive failures before automated health degrades. Starts at 2 failures by default.'),
                        TextInput::make('default_recovery_threshold')
                            ->default($defaults['default_recovery_threshold'])
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('New checks inherit this many consecutive passing runs before automated health recovers. Starts at 1 passing run by default.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Probe registration security')
                    ->description('Package-enabled Laravel apps use this monitor-side bearer token when they push registration payloads into the private integration API.')
                    ->schema([
                        TextInput::make('probe_registration_token')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                            ->helperText('Set this before you ask another Laravel app to run php artisan status-probe:register.')
                            ->suffixAction(
                                Action::make('generate_probe_token')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->tooltip('Generate a secure push token')
                                    ->action(function (Set $set): void {
                                        $set('probe_registration_token', Str::random(40));
                                    }),
                                isInline: true,
                            )
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('SEO and preview text')
                    ->description('Used for browser tabs, search previews, and link embeds for the public status page.')
                    ->schema([
                        TextInput::make('seo_title')
                            ->default($defaults['seo_title'])
                            ->maxLength(255)
                            ->helperText('Prefills from the current brand name so the page is ready immediately, but a custom title can improve search and sharing previews.'),
                        Textarea::make('seo_description')
                            ->default($defaults['seo_description'])
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Short summary of what the status page covers and who it serves. Starts from the brand tagline on first setup.'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform settings summary')
                    ->description('Review the public brand settings, registration token state, and default monitoring thresholds in one contained overview.')
                    ->schema([
                        TextEntry::make('brand_name'),
                        TextEntry::make('brand_tagline')
                            ->placeholder('No tagline configured'),
                        TextEntry::make('support_email')
                            ->placeholder('No public support email'),
                        TextEntry::make('probe_registration_token')
                            ->placeholder('Not configured')
                            ->copyable(),
                        TextEntry::make('uptime_window_days'),
                        TextEntry::make('raw_run_retention_days'),
                        TextEntry::make('default_failure_threshold'),
                        TextEntry::make('default_recovery_threshold'),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
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
                TextColumn::make('probe_registration_token')
                    ->label('Push token')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Configured' : 'Missing'),
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
