<?php

namespace Mbs047\LaravelStatusProbe\Tests\Unit;

use Mbs047\LaravelStatusProbe\Contributors\SchedulerProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\Support\HeartbeatRepository;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class SchedulerProbeContributorTest extends TestCase
{
    public function test_scheduler_probe_reports_major_outage_when_the_heartbeat_is_missing(): void
    {
        $result = $this->app->make(SchedulerProbeContributor::class)->resolve();

        $this->assertSame(ProbeStatus::MajorOutage, $result->status);
    }

    public function test_scheduler_probe_reports_operational_when_the_heartbeat_is_fresh(): void
    {
        $this->app->make(HeartbeatRepository::class)->touch('scheduler');

        $result = $this->app->make(SchedulerProbeContributor::class)->resolve();

        $this->assertSame(ProbeStatus::Operational, $result->status);
    }
}
