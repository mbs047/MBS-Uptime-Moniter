<?php

namespace Mbs047\LaravelStatusProbe\Contributors;

use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;
use Mbs047\LaravelStatusProbe\Support\HeartbeatRepository;

class SchedulerProbeContributor implements ProbeContributor
{
    public function __construct(
        protected readonly HeartbeatRepository $heartbeats,
    ) {}

    public function key(): string
    {
        return 'scheduler';
    }

    public function label(): string
    {
        return 'Scheduler';
    }

    public function description(): ?string
    {
        return 'Scheduler heartbeat freshness from the monitored app cron loop.';
    }

    public function defaultCheckConfig(): array
    {
        return [];
    }

    public function resolve(): ProbeResult
    {
        $lastSeen = $this->heartbeats->lastSeen('scheduler');
        $maxAge = (int) config('status-probe.heartbeat.scheduler_max_age_seconds', 180);

        if (! $lastSeen || now()->diffInSeconds($lastSeen) > $maxAge) {
            return new ProbeResult(
                ProbeStatus::MajorOutage,
                'Scheduler heartbeat is stale.',
                ['last_seen_at' => $lastSeen?->toIso8601String()],
            );
        }

        return new ProbeResult(
            ProbeStatus::Operational,
            'Scheduler heartbeat is fresh.',
            ['last_seen_at' => $lastSeen->toIso8601String()],
        );
    }
}
