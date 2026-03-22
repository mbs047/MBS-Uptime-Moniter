<?php

namespace Mbs047\LaravelStatusProbe\Tests\Unit;

use Mbs047\LaravelStatusProbe\Contributors\CacheProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class CacheProbeContributorTest extends TestCase
{
    public function test_cache_probe_reports_operational_when_the_store_round_trip_succeeds(): void
    {
        $result = $this->app->make(CacheProbeContributor::class)->resolve();

        $this->assertSame(ProbeStatus::Operational, $result->status);
    }
}
