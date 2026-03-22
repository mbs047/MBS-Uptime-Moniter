<?php

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use App\Filament\Resources\Admins\AdminResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected ?string $subheading = 'Use invites for normal onboarding, and keep direct admin creation for tightly controlled internal access.';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('invite_admin')
                    ->label('Invite admin')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->url(AdminInviteResource::getUrl('create')),
                CreateAction::make()
                    ->label('Create admin manually')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('gray'),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button(),
        ];
    }
}
