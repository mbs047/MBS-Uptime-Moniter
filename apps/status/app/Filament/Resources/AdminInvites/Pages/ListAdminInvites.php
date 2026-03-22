<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use App\Filament\Resources\Admins\AdminResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAdminInvites extends ListRecords
{
    protected static string $resource = AdminInviteResource::class;

    protected ?string $subheading = 'Track who still needs access, which invite links have expired, and when a teammate has already completed onboarding.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Send invite')
                ->icon(Heroicon::OutlinedEnvelopeOpen),
            Action::make('view_admins')
                ->label('View admins')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('gray')
                ->url(AdminResource::getUrl('index')),
        ];
    }
}
