<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use App\Mail\AdminInviteMail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateAdminInvite extends CreateRecord
{
    protected static string $resource = AdminInviteResource::class;

    protected ?string $subheading = 'Invite teammates with a time-limited link so they can choose their own password and start with a verified identity.';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['token'] = Str::random(48);
        $data['created_by_admin_id'] = auth('admin')->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        Mail::to($this->record->email)->queue(new AdminInviteMail($this->record));

        Notification::make()
            ->title('Admin invite queued for delivery.')
            ->body('The acceptance link is also available on the invite record if you want to share it manually.')
            ->success()
            ->send();
    }
}
