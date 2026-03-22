<?php

namespace Tests\Unit;

use App\Enums\CheckType;
use App\Enums\IncidentSeverity;
use App\Models\PlatformSetting;
use App\Support\Filament\FormDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_settings_defaults_use_product_baseline_and_config_fallbacks(): void
    {
        config([
            'app.url' => 'https://status.example.test/',
            'mail.from.name' => 'Monitor Mail',
            'mail.from.address' => 'ops@example.test',
        ]);

        $defaults = FormDefaults::platformSettings();

        $this->assertSame('Status Center', $defaults['brand_name']);
        $this->assertSame('Operational visibility for critical services', $defaults['brand_tagline']);
        $this->assertSame('https://status.example.test', $defaults['brand_url']);
        $this->assertSame('ops@example.test', $defaults['support_email']);
        $this->assertSame('Monitor Mail', $defaults['mail_from_name']);
        $this->assertSame('ops@example.test', $defaults['mail_from_address']);
        $this->assertSame('Status Center', $defaults['seo_title']);
        $this->assertSame('Operational visibility for critical services', $defaults['seo_description']);
        $this->assertSame(90, $defaults['uptime_window_days']);
        $this->assertSame(14, $defaults['raw_run_retention_days']);
        $this->assertSame(2, $defaults['default_failure_threshold']);
        $this->assertSame(1, $defaults['default_recovery_threshold']);
    }

    public function test_platform_settings_defaults_prefer_saved_values_over_environment_fallbacks(): void
    {
        config([
            'app.url' => 'https://status.example.test',
            'mail.from.name' => 'Monitor Mail',
            'mail.from.address' => 'ops@example.test',
        ]);

        PlatformSetting::query()->create([
            'brand_name' => 'Acme Status',
            'brand_tagline' => 'Critical systems at a glance',
            'brand_url' => 'https://acme.example.test/status',
            'support_email' => 'status@acme.example.test',
            'mail_from_name' => 'Acme Operations',
            'mail_from_address' => 'alerts@acme.example.test',
            'seo_title' => 'Acme Status Page',
            'seo_description' => 'Live production status for Acme systems.',
            'uptime_window_days' => 120,
            'raw_run_retention_days' => 21,
            'default_failure_threshold' => 3,
            'default_recovery_threshold' => 2,
        ]);

        $defaults = FormDefaults::platformSettings();

        $this->assertSame('Acme Status', $defaults['brand_name']);
        $this->assertSame('Critical systems at a glance', $defaults['brand_tagline']);
        $this->assertSame('https://acme.example.test/status', $defaults['brand_url']);
        $this->assertSame('status@acme.example.test', $defaults['support_email']);
        $this->assertSame('Acme Operations', $defaults['mail_from_name']);
        $this->assertSame('alerts@acme.example.test', $defaults['mail_from_address']);
        $this->assertSame('Acme Status Page', $defaults['seo_title']);
        $this->assertSame('Live production status for Acme systems.', $defaults['seo_description']);
        $this->assertSame(120, $defaults['uptime_window_days']);
        $this->assertSame(21, $defaults['raw_run_retention_days']);
        $this->assertSame(3, $defaults['default_failure_threshold']);
        $this->assertSame(2, $defaults['default_recovery_threshold']);
    }

    public function test_shared_operational_defaults_match_the_expected_monitoring_baseline(): void
    {
        $this->assertSame(CheckType::Http->value, FormDefaults::checkType());
        $this->assertSame([200], FormDefaults::httpExpectedStatuses());
        $this->assertSame(443, FormDefaults::httpsPort());
        $this->assertSame(IncidentSeverity::Degraded->value, FormDefaults::incidentSeverity());
    }
}
