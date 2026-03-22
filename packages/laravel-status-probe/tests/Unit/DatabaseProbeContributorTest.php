<?php

namespace Mbs047\LaravelStatusProbe\Tests\Unit;

use Mbs047\LaravelStatusProbe\Contributors\DatabaseProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class DatabaseProbeContributorTest extends TestCase
{
    public function test_database_probe_reports_operational_when_the_connection_is_available(): void
    {
        $result = $this->app->make(DatabaseProbeContributor::class)->resolve();

        $this->assertSame(ProbeStatus::Operational, $result->status);
    }
}
