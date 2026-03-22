<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Enums\IncidentStatus;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Services\Status\IncidentNotifier;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if ($this->record->status === IncidentStatus::Published && $this->record->published_at) {
            app(IncidentNotifier::class)->send($this->record, 'created');
        }
    }
}
