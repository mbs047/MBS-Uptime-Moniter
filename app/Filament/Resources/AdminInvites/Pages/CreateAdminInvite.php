<?php

namespace App\Filament\Resources\AdminInvites\Pages;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use App\Mail\AdminInviteMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminInvite extends CreateRecord
{
    protected static string $resource = AdminInviteResource::class;

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
    }
}
