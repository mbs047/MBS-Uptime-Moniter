<?php

namespace Mbs047\LaravelStatusProbe\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

class HeartbeatRepository
{
    public function __construct(
        protected readonly CacheFactory $cache,
    ) {}

    public function touch(string $subject): void
    {
        $this->store()->forever($this->key($subject), now()->timestamp);
    }

    public function lastSeen(string $subject): ?Carbon
    {
        $timestamp = $this->store()->get($this->key($subject));

        if (! is_numeric($timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp);
    }

    protected function store(): Repository
    {
        $store = config('status-probe.heartbeat.store');

        return filled($store)
            ? $this->cache->store((string) $store)
            : $this->cache->store();
    }

    protected function key(string $subject): string
    {
        return 'status-probe:heartbeat:'.$subject;
    }
}
