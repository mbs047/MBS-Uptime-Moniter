<?php

namespace Tests\Feature;

use App\Filament\Pages\ApiDocsPage;
use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\RemoteIntegration;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiDocsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_docs_page_renders_endpoint_catalog(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(ApiDocsPage::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('/api/status/summary')
            ->assertSee('/api/status/services')
            ->assertSee('/api/status/incidents')
            ->assertSee('/api/status/subscribers')
            ->assertSee('/api/integrations/probes/register');
    }

    public function test_summary_preview_returns_a_live_response_body(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $component = Livewire::test(ApiDocsPage::class)
            ->call('testEndpoint', 'summary');

        $responses = $component->get('endpointResponses');

        $this->assertSame(200, data_get($responses, 'summary.status'));
        $this->assertTrue((bool) data_get($responses, 'summary.ok'));
        $this->assertStringContainsString('"overall_status"', (string) data_get($responses, 'summary.body'));
    }

    public function test_subscriber_preview_rolls_back_changes_after_testing(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $component = Livewire::test(ApiDocsPage::class)
            ->set('subscriberPreviewEmail', 'docs-preview@example.test')
            ->call('testEndpoint', 'subscribers');

        $responses = $component->get('endpointResponses');

        $this->assertSame(200, data_get($responses, 'subscribers.status'));
        $this->assertDatabaseMissing('subscribers', [
            'email' => 'docs-preview@example.test',
        ]);
        $this->assertSame(0, Subscriber::query()->count());
    }

    public function test_probe_registration_preview_rolls_back_generated_records(): void
    {
        $admin = Admin::factory()->create();

        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
            'probe_registration_token' => 'monitor-secret',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
        ]);

        $this->actingAs($admin, 'admin');

        $component = Livewire::test(ApiDocsPage::class)
            ->call('testEndpoint', 'probe_registration');

        $responses = $component->get('endpointResponses');

        $this->assertSame(200, data_get($responses, 'probe_registration.status'));
        $this->assertTrue((bool) data_get($responses, 'probe_registration.ok'));
        $this->assertSame(0, RemoteIntegration::query()->count());
    }
}
