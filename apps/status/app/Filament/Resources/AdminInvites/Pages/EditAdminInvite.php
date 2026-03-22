<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminInvite extends EditRecord
{
    protected static string $resource = AdminInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
