<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminInvite extends ViewRecord
{
    protected static string $resource = AdminInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
