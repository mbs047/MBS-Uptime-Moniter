<?php

namespace Mbs047\LaravelStatusProbe\Tests\Unit;

use Mbs047\LaravelStatusProbe\Support\MonitorHttpOptions;
use Mbs047\LaravelStatusProbe\Tests\TestCase;

class MonitorHttpOptionsTest extends TestCase
{
    public function test_monitor_requests_verify_tls_by_default(): void
    {
        config([
            'status-probe.monitor.verify' => true,
            'status-probe.monitor.ca_path' => null,
        ]);

        $this->assertSame(['verify' => true], MonitorHttpOptions::make());
    }

    public function test_monitor_requests_can_disable_verification_from_config(): void
    {
        config([
            'status-probe.monitor.verify' => false,
            'status-probe.monitor.ca_path' => null,
        ]);

        $this->assertSame(['verify' => false], MonitorHttpOptions::make());
    }

    public function test_monitor_requests_prefer_a_custom_ca_bundle_path(): void
    {
        config([
            'status-probe.monitor.verify' => true,
            'status-probe.monitor.ca_path' => '/tmp/local-ca.pem',
        ]);

        $this->assertSame(['verify' => '/tmp/local-ca.pem'], MonitorHttpOptions::make());
    }

    public function test_insecure_flag_overrides_other_tls_settings(): void
    {
        config([
            'status-probe.monitor.verify' => true,
            'status-probe.monitor.ca_path' => '/tmp/local-ca.pem',
        ]);

        $this->assertSame(['verify' => false], MonitorHttpOptions::make(insecure: true));
    }
}
