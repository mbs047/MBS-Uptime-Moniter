<?php

namespace Mbs047\LaravelStatusProbe\Contributors;

use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;
use Mbs047\LaravelStatusProbe\Support\HeartbeatRepository;

class QueueProbeContributor implements ProbeContributor
{
    public function __construct(
        protected readonly HeartbeatRepository $heartbeats,
    ) {}

    public function key(): string
    {
        return 'queue';
    }

    public function label(): string
    {
        return 'Queue Worker';
    }

    public function description(): ?string
    {
        return 'Queue worker heartbeat refreshed from the worker loop.';
    }

    public function defaultCheckConfig(): array
    {
        return [];
    }

    public function resolve(): ProbeResult
    {
        $lastSeen = $this->heartbeats->lastSeen('queue');
        $maxAge = (int) config('status-probe.heartbeat.queue_max_age_seconds', 180);

        if (! $lastSeen || now()->diffInSeconds($lastSeen) > $maxAge) {
            return new ProbeResult(
                ProbeStatus::MajorOutage,
                'Queue heartbeat is stale.',
                ['last_seen_at' => $lastSeen?->toIso8601String()],
            );
        }

        return new ProbeResult(
            ProbeStatus::Operational,
            'Queue heartbeat is fresh.',
            ['last_seen_at' => $lastSeen->toIso8601String()],
        );
    }
}
