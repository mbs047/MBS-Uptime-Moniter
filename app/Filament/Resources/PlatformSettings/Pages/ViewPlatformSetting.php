<?php

namespace App\Filament\Resources\PlatformSettings\Pages;

use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformSetting extends ViewRecord
{
    protected static string $resource = PlatformSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
