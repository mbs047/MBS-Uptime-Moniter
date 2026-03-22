<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewAdminInvite extends ViewRecord
{
    protected static string $resource = AdminInviteResource::class;

    protected ?string $subheading = 'Review the invite state, copy the acceptance link, or resend the invite without leaving the record.';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                AdminInviteResource::makeResendInviteAction(),
                EditAction::make()
                    ->slideOver(),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button(),
        ];
    }
}
