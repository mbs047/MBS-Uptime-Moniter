<?php

namespace App\Services\Status;

use App\Enums\ComponentStatus;
use App\Models\Component;
use App\Models\ComponentDailyUptime;
use App\Models\Incident;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyUptimeAggregator
{
    public function refreshForDay(CarbonInterface $day): void
    {
        $day = Carbon::instance($day)->timezone(config('app.timezone'))->startOfDay();

        Component::query()
            ->with([
                'checks.runs' => fn ($query) => $query
                    ->whereBetween('started_at', [$day, $day->copy()->endOfDay()])
                    ->orderBy('started_at'),
            ])
            ->chunkById(50, function ($components) use ($day): void {
                foreach ($components as $component) {
                    $this->refreshComponentForDay($component, $day);
                }
            });
    }

    public function refreshComponentForDay(Component $component, CarbonInterface $day): ComponentDailyUptime
    {
        $day = Carbon::instance($day)->timezone(config('app.timezone'))->startOfDay();
        $maintenanceWindows = $this->maintenanceWindowsForDay($component, $day);
        $healthySlots = 0;
        $observedSlots = 0;
        $maintenanceSlots = 0;
        $noDataSlots = 0;

        foreach ($component->checks->where('enabled', true) as $check) {
            $runs = $check->runs->values();
            $runIndex = 0;
            $slotMinutes = max(1, $check->interval_minutes);

            for ($slotStart = $day->copy(); $slotStart->lt($day->copy()->endOfDay()); $slotStart->addMinutes($slotMinutes)) {
                $slotEnd = $slotStart->copy()->addMinutes($slotMinutes);

                if ($this->isWithinMaintenance($maintenanceWindows, $slotStart)) {
                    $maintenanceSlots++;

                    continue;
                }

                while (($runs[$runIndex] ?? null) && $runs[$runIndex]->started_at->lt($slotStart)) {
                    $runIndex++;
                }

                $run = $runs[$runIndex] ?? null;

                if (! $run || $run->started_at->gte($slotEnd)) {
                    $noDataSlots++;

                    continue;
                }

                $observedSlots++;

                if (($run->severity ?? ComponentStatus::Operational) === ComponentStatus::Operational) {
                    $healthySlots++;
                }
            }
        }

        $uptimePercentage = $observedSlots > 0
            ? round(($healthySlots / $observedSlots) * 100, 2)
            : 0;

        return ComponentDailyUptime::query()->updateOrCreate(
            [
                'component_id' => $component->id,
                'day' => $day->toDateString(),
            ],
            [
                'healthy_slots' => $healthySlots,
                'observed_slots' => $observedSlots,
                'maintenance_slots' => $maintenanceSlots,
                'no_data_slots' => $noDataSlots,
                'uptime_percentage' => $uptimePercentage,
            ],
        );
    }

    /**
     * @return Collection<int, array{start: Carbon, end: Carbon}>
     */
    protected function maintenanceWindowsForDay(Component $component, CarbonInterface $day): Collection
    {
        $dayStart = Carbon::instance($day)->startOfDay();
        $dayEnd = Carbon::instance($day)->endOfDay();

        return Incident::query()
            ->activeAt($dayEnd)
            ->where('severity', ComponentStatus::Maintenance->value)
            ->where(function ($query) use ($component): void {
                $query->whereHas('components', fn ($query) => $query->whereKey($component->id))
                    ->orWhereHas('services', fn ($query) => $query->whereKey($component->service_id));
            })
            ->get()
            ->map(function (Incident $incident) use ($dayStart, $dayEnd): array {
                return [
                    'start' => Carbon::parse($incident->scheduled_starts_at ?? $incident->starts_at ?? $dayStart)->max($dayStart),
                    'end' => Carbon::parse($incident->scheduled_ends_at ?? $incident->resolved_at ?? $dayEnd)->min($dayEnd),
                ];
            });
    }

    /**
     * @param  Collection<int, array{start: Carbon, end: Carbon}>  $windows
     */
    protected function isWithinMaintenance(Collection $windows, CarbonInterface $moment): bool
    {
        return $windows->contains(fn (array $window): bool => $moment->betweenIncluded($window['start'], $window['end']));
    }
}
