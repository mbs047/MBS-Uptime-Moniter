<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Enums\IncidentStatus;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Services\Status\IncidentNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditIncident extends EditRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug((string) $data['title']);

        if (($data['status'] ?? null) === IncidentStatus::Published->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) === IncidentStatus::Resolved->value && blank($data['resolved_at'] ?? null)) {
            $data['resolved_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->published_at) {
            return;
        }

        if ($this->record->status === IncidentStatus::Resolved && $this->record->resolved_at && $this->record->wasChanged('resolved_at')) {
            app(IncidentNotifier::class)->send($this->record, 'resolved');

            return;
        }

        if ($this->record->wasChanged('published_at') || $this->record->wasChanged('status')) {
            app(IncidentNotifier::class)->send($this->record, 'created');

            return;
        }

        app(IncidentNotifier::class)->send($this->record, 'updated');
    }
}
