<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminInvite extends EditRecord
{
    protected static string $resource = AdminInviteResource::class;

    protected ?string $subheading = 'Adjust the invite window or recipient details, then resend if the original delivery did not reach the user.';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            AdminInviteResource::makeResendInviteAction(),
            DeleteAction::make(),
        ];
    }
}
