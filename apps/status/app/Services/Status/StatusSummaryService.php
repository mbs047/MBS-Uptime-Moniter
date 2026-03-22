<?php

namespace App\Services\Status;

use App\Enums\ComponentStatus;
use App\Models\Component;
use App\Models\Incident;
use App\Models\PlatformSetting;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StatusSummaryService
{
    public function overviewPayload(): array
    {
        $settings = PlatformSetting::current();
        $windowDays = $settings->uptime_window_days ?: 90;
        $services = $this->servicesQuery($windowDays)->get();
        $publishedIncidents = $this->publishedIncidents();
        $activeIncidents = $this->activeIncidents($publishedIncidents);

        return [
            'settings' => $settings,
            'summary' => $this->summaryPayload($services, $activeIncidents, $settings),
            'services' => $this->serializeServices($services, $windowDays, $publishedIncidents, $activeIncidents),
            'activeIncidents' => $this->serializeIncidents($activeIncidents),
            'incidentHistory' => $this->serializeIncidents($publishedIncidents->take(10)),
            'uptimeWindowDays' => $windowDays,
        ];
    }

    public function summaryPayload(?EloquentCollection $services = null, ?Collection $activeIncidents = null, ?PlatformSetting $settings = null): array
    {
        $settings ??= PlatformSetting::current();
        $services ??= $this->servicesQuery($settings->uptime_window_days ?: 90)->get();
        $activeIncidents ??= $this->activeIncidents($this->publishedIncidents());
        $overallStatus = $services
            ->map(fn (Service $service) => $service->status ?? ComponentStatus::Operational)
            ->push(...$activeIncidents->map(fn (Incident $incident) => $incident->severity->toComponentStatus()))
            ->sortByDesc(fn (ComponentStatus $status) => $status->rank())
            ->first() ?? ComponentStatus::Operational;

        return [
            'overall_status' => $overallStatus->value,
            'generated_at' => now()->toIso8601String(),
            'active_incident_count' => $activeIncidents->count(),
            'affected_component_count' => $services->sum(fn (Service $service) => $service->components->count()),
            'uptime_window_days' => $settings->uptime_window_days ?: 90,
            'brand' => [
                'name' => $settings->brand_name,
                'tagline' => $settings->brand_tagline,
                'support_email' => $settings->support_email,
            ],
        ];
    }

    public function servicesPayload(): array
    {
        $settings = PlatformSetting::current();
        $windowDays = $settings->uptime_window_days ?: 90;
        $publishedIncidents = $this->publishedIncidents();
        $activeIncidents = $this->activeIncidents($publishedIncidents);
        $services = $this->servicesQuery($windowDays)->get();

        return $this->serializeServices($services, $windowDays, $publishedIncidents, $activeIncidents);
    }

    public function incidentsPayload(): array
    {
        return $this->serializeIncidents($this->publishedIncidents());
    }

    public function historyPayload(): array
    {
        $settings = PlatformSetting::current();
        $publishedIncidents = $this->publishedIncidents();
        $activeIncidents = $this->activeIncidents($publishedIncidents);

        return [
            'settings' => $settings,
            'activeIncidents' => $this->serializeIncidents($activeIncidents),
            'incidentHistory' => $this->serializeIncidents($publishedIncidents),
            'historyStats' => [
                'published_incidents' => $publishedIncidents->count(),
                'active_incidents' => $activeIncidents->count(),
                'resolved_incidents' => $publishedIncidents->filter(fn (Incident $incident) => $incident->resolved_at !== null)->count(),
                'maintenance_incidents' => $publishedIncidents->filter(fn (Incident $incident) => $incident->severity->value === 'maintenance')->count(),
            ],
        ];
    }

    protected function servicesQuery(int $windowDays)
    {
        $fromDay = Carbon::today(config('app.timezone'))->subDays($windowDays - 1);

        return Service::query()
            ->where('is_public', true)
            ->with([
                'components' => fn ($query) => $query
                    ->where('is_public', true)
                    ->with([
                        'dailyUptimes' => fn ($query) => $query
                            ->where('day', '>=', $fromDay->toDateString())
                            ->orderBy('day'),
                    ])
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order');
    }

    protected function publishedIncidents(): Collection
    {
        return Incident::query()
            ->published()
            ->with([
                'services:id,name,slug',
                'components:id,service_id,display_name',
                'updates' => fn ($query) => $query->whereNotNull('published_at')->latest('published_at'),
            ])
            ->orderByDesc('published_at')
            ->get()
            ->sortByDesc(fn (Incident $incident) => $this->incidentSortKey($incident))
            ->values();
    }

    protected function activeIncidents(Collection $incidents): Collection
    {
        return $incidents
            ->filter(fn (Incident $incident) => $incident->resolved_at === null)
            ->values();
    }

    protected function serializeServices(Collection $services, int $windowDays, Collection $publishedIncidents, Collection $activeIncidents): array
    {
        return $services->map(function (Service $service) use ($windowDays, $publishedIncidents, $activeIncidents): array {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'description' => $service->description,
                'status' => $service->status?->value ?? ComponentStatus::Operational->value,
                'component_count' => $service->components->count(),
                'uptime_90d_percent' => $this->serviceUptimePercentage($service),
                'components' => $service->components->map(fn (Component $component) => [
                    'id' => $component->id,
                    'display_name' => $component->display_name,
                    'description' => $component->description,
                    'status' => $component->status?->value ?? ComponentStatus::Operational->value,
                    'uptime_90d_percent' => $this->componentUptimePercentage($component),
                    'uptime_bars' => $this->componentUptimeBars($component, $service, $windowDays, $publishedIncidents),
                    'active_incidents' => $this->componentIncidentRefs($component, $service, $activeIncidents),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    protected function serviceUptimePercentage(Service $service): float
    {
        $healthy = $service->components
            ->flatMap(fn (Component $component) => $component->dailyUptimes)
            ->sum('healthy_slots');

        $observed = $service->components
            ->flatMap(fn (Component $component) => $component->dailyUptimes)
            ->sum('observed_slots');

        return $observed > 0 ? round(($healthy / $observed) * 100, 2) : 0;
    }

    protected function serializeIncidents(Collection $incidents): array
    {
        return $incidents->map(function (Incident $incident): array {
            return [
                'id' => $incident->id,
                'slug' => $incident->slug,
                'title' => $incident->title,
                'summary' => $incident->summary,
                'status' => $incident->status->value,
                'severity' => $incident->severity->value,
                'published_at' => optional($incident->published_at)->toIso8601String(),
                'resolved_at' => optional($incident->resolved_at)->toIso8601String(),
                'started_at' => optional($incident->starts_at ?? $incident->scheduled_starts_at)->toIso8601String(),
                'affected_service_ids' => $incident->services->pluck('id')->all(),
                'affected_component_ids' => $incident->components->pluck('id')->all(),
                'latest_update' => optional($incident->updates->first())->body,
            ];
        })->values()->all();
    }

    protected function componentUptimePercentage(Component $component): float
    {
        $observed = $component->dailyUptimes->sum('observed_slots');
        $healthy = $component->dailyUptimes->sum('healthy_slots');

        return $observed > 0 ? round(($healthy / $observed) * 100, 2) : 0;
    }

    protected function componentUptimeBars(Component $component, Service $service, int $windowDays, Collection $publishedIncidents): array
    {
        $bars = collect();
        $byDay = $component->dailyUptimes->keyBy(fn ($uptime) => $uptime->day->toDateString());

        foreach (range($windowDays - 1, 0) as $offset) {
            $day = Carbon::today(config('app.timezone'))->subDays($offset);
            $dayKey = $day->toDateString();
            $uptime = $byDay->get($dayKey);

            if (! $uptime) {
                $bars->push([
                    'day' => $dayKey,
                    'date_label' => $day->format('D, j M Y'),
                    'state' => 'no_data',
                    'percentage' => null,
                    'messages' => [[
                        'severity' => 'no_data',
                        'message' => $this->defaultUptimeMessage('no_data'),
                    ]],
                ]);

                continue;
            }

            $state = $this->resolveUptimeState((float) $uptime->uptime_percentage, $uptime->observed_slots, $uptime->maintenance_slots);
            $messages = $this->componentIncidentMessages(
                $component,
                $service,
                $day->copy()->startOfDay(),
                $day->copy()->endOfDay(),
                $publishedIncidents,
            );
            $percentage = $state === 'no_data'
                ? null
                : (float) $uptime->uptime_percentage;

            $bars->push([
                'day' => $dayKey,
                'date_label' => $day->format('D, j M Y'),
                'state' => $state,
                'percentage' => $percentage,
                'messages' => $messages !== []
                    ? $messages
                    : [[
                        'severity' => $state,
                        'message' => $this->defaultUptimeMessage($state),
                    ]],
            ]);
        }

        return $bars->all();
    }

    protected function resolveUptimeState(?float $uptimePercentage, int $observedSlots, int $maintenanceSlots): string
    {
        return match (true) {
            $maintenanceSlots > 0 && $observedSlots === 0 => 'maintenance',
            $observedSlots === 0 => 'no_data',
            $uptimePercentage >= 99.95 => 'operational',
            $uptimePercentage >= 99.0 => 'degraded',
            $uptimePercentage >= 95.0 => 'partial_outage',
            default => 'major_outage',
        };
    }

    protected function componentIncidentMessages(Component $component, Service $service, Carbon $dayStart, Carbon $dayEnd, Collection $publishedIncidents): array
    {
        return $publishedIncidents
            ->filter(function (Incident $incident) use ($component, $service, $dayStart, $dayEnd): bool {
                $affectsService = $incident->services->contains('id', $service->id)
                    || $incident->components->contains('id', $component->id);

                return $affectsService && $this->incidentTouchesDay($incident, $dayStart, $dayEnd);
            })
            ->map(function (Incident $incident) use ($dayEnd): array {
                $message = optional(
                    $incident->updates
                        ->filter(fn ($update) => ($update->published_at ?? $update->created_at)?->lte($dayEnd))
                        ->sortByDesc(fn ($update) => ($update->published_at ?? $update->created_at)?->getTimestamp() ?? 0)
                        ->first()
                )->body ?? $incident->summary ?? $incident->title;

                return [
                    'severity' => $incident->severity->value,
                    'message' => $message,
                ];
            })
            ->unique('message')
            ->values()
            ->all();
    }

    protected function incidentTouchesDay(Incident $incident, Carbon $dayStart, Carbon $dayEnd): bool
    {
        $incidentStart = $incident->starts_at
            ?? $incident->scheduled_starts_at
            ?? $incident->published_at
            ?? $incident->created_at;

        $incidentEnd = $incident->resolved_at ?? $incident->scheduled_ends_at;

        if ($incidentStart && $incidentStart->gt($dayEnd)) {
            return false;
        }

        if ($incidentEnd && $incidentEnd->lt($dayStart)) {
            return false;
        }

        return true;
    }

    protected function defaultUptimeMessage(string $state): string
    {
        return match ($state) {
            'operational' => 'All monitored components operated normally on this day.',
            'maintenance' => 'Only scheduled maintenance was recorded on this day.',
            'degraded' => 'Automated checks detected degraded performance on this day.',
            'partial_outage' => 'Automated checks recorded a partial outage on this day.',
            'major_outage' => 'Automated checks recorded a major outage on this day.',
            default => 'No uptime data was recorded for this day.',
        };
    }

    protected function componentIncidentRefs(Component $component, Service $service, Collection $activeIncidents): array
    {
        return $activeIncidents
            ->filter(function (Incident $incident) use ($component, $service): bool {
                return $incident->components->contains('id', $component->id)
                    || $incident->services->contains('id', $service->id);
            })
            ->map(fn (Incident $incident) => [
                'slug' => $incident->slug,
                'title' => $incident->title,
                'severity' => $incident->severity->value,
            ])
            ->values()
            ->all();
    }

    protected function incidentSortKey(Incident $incident): int
    {
        $activeWeight = $incident->resolved_at === null ? 1_000_000_000 : 0;
        $publishedAt = ($incident->published_at ?? $incident->created_at)->timestamp;

        return $activeWeight + $publishedAt;
    }
}
