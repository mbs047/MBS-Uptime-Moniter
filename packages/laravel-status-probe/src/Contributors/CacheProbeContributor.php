<?php

namespace Mbs047\LaravelStatusProbe\Contributors;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

class CacheProbeContributor implements ProbeContributor
{
    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache';
    }

    public function description(): ?string
    {
        return 'Configured cache store read and write cycle.';
    }

    public function defaultCheckConfig(): array
    {
        return [];
    }

    public function resolve(): ProbeResult
    {
        $store = filled(config('status-probe.heartbeat.store'))
            ? Cache::store((string) config('status-probe.heartbeat.store'))
            : Cache::store();
        $key = 'status-probe:cache-check:'.Str::random(12);

        try {
            $store->put($key, 'ok', 60);
            $value = $store->get($key);
            $store->forget($key);

            if ($value !== 'ok') {
                return new ProbeResult(
                    ProbeStatus::MajorOutage,
                    'Cache round trip did not return the expected value.',
                );
            }

            return new ProbeResult(
                ProbeStatus::Operational,
                'Cache round trip succeeded.',
            );
        } catch (\Throwable $exception) {
            return new ProbeResult(
                ProbeStatus::MajorOutage,
                $exception->getMessage(),
            );
        }
    }
}
