<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminInvites extends ListRecords
{
    protected static string $resource = AdminInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
