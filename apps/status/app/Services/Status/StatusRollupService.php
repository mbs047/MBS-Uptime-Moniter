<?php

namespace App\Services\Status;

use App\Enums\ComponentStatus;
use App\Models\Component;
use App\Models\Incident;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class StatusRollupService
{
    public function recalculateComponent(Component|int $component): ComponentStatus
    {
        $component = $component instanceof Component
            ? $component->loadMissing('checks', 'service')
            : Component::query()->with('checks', 'service')->findOrFail($component);

        $statuses = $component->checks
            ->where('enabled', true)
            ->map(fn ($check) => $check->latest_severity ?? ComponentStatus::Operational)
            ->values();

        $passingChecks = $statuses->filter(fn (ComponentStatus $status) => $status === ComponentStatus::Operational)->count();
        $hasHardFailures = $statuses->contains(ComponentStatus::MajorOutage);
        $hasSoftFailures = $statuses->contains(ComponentStatus::Degraded);

        $automatedStatus = match (true) {
            $hasHardFailures && $passingChecks > 0 => ComponentStatus::PartialOutage,
            $hasHardFailures => ComponentStatus::MajorOutage,
            $hasSoftFailures => ComponentStatus::Degraded,
            default => ComponentStatus::Operational,
        };

        $incidentStatus = $this->activeIncidentStatusForComponent($component);
        $finalStatus = $incidentStatus && $incidentStatus->rank() > $automatedStatus->rank()
            ? $incidentStatus
            : $automatedStatus;

        $component->forceFill([
            'automated_status' => $automatedStatus,
            'status' => $finalStatus,
            'last_status_changed_at' => $component->status !== $finalStatus ? now() : $component->last_status_changed_at,
        ])->save();

        $this->recalculateService($component->service);

        return $finalStatus;
    }

    public function recalculateService(Service|int $service): ComponentStatus
    {
        $service = $service instanceof Service
            ? $service->loadMissing('components')
            : Service::query()->with('components')->findOrFail($service);

        $statuses = $service->components
            ->where('is_public', true)
            ->map(fn (Component $component) => $component->status ?? ComponentStatus::Operational);

        $status = $statuses
            ->sortByDesc(fn (ComponentStatus $status) => $status->rank())
            ->first() ?? ComponentStatus::Operational;

        $service->forceFill([
            'status' => $status,
            'last_status_changed_at' => $service->status !== $status ? now() : $service->last_status_changed_at,
        ])->save();

        return $status;
    }

    public function activeIncidentStatusForComponent(Component $component): ?ComponentStatus
    {
        $incident = Incident::query()
            ->activeAt(now())
            ->where(function (Builder $query) use ($component): void {
                $query->whereHas('components', fn (Builder $query) => $query->whereKey($component->id))
                    ->orWhereHas('services', fn (Builder $query) => $query->whereKey($component->service_id));
            })
            ->get()
            ->sortByDesc(fn (Incident $incident) => $incident->severity->toComponentStatus()->rank())
            ->first();

        return $incident?->severity->toComponentStatus();
    }
}
