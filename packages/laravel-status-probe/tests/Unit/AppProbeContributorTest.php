<?php

namespace Mbs047\LaravelStatusProbe\Tests\Unit;

use Mbs047\LaravelStatusProbe\Contributors\AppProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use PHPUnit\Framework\TestCase;

class AppProbeContributorTest extends TestCase
{
    public function test_application_probe_reports_operational(): void
    {
        $result = (new AppProbeContributor())->resolve();

        $this->assertSame(ProbeStatus::Operational, $result->status);
    }
}
