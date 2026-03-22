<?php

namespace Tests\Feature;

use App\Enums\ComponentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Component;
use App\Models\ComponentDailyUptime;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\PlatformSetting;
use App\Models\Service;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class PublicStatusTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_public_pages_and_api_endpoints_render_status_data(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility for critical services',
        ]);

        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
            'status' => ComponentStatus::MajorOutage,
            'is_public' => true,
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Public API',
            'status' => ComponentStatus::MajorOutage,
            'automated_status' => ComponentStatus::Degraded,
            'is_public' => true,
        ]);

        ComponentDailyUptime::query()->create([
            'component_id' => $component->id,
            'day' => now()->toDateString(),
            'healthy_slots' => 95,
            'observed_slots' => 100,
            'maintenance_slots' => 0,
            'no_data_slots' => 0,
            'uptime_percentage' => 95.00,
        ]);

        $incident = Incident::query()->create([
            'title' => 'API outage',
            'slug' => 'api-outage',
            'summary' => 'Requests are intermittently failing.',
            'status' => IncidentStatus::Published,
            'severity' => IncidentSeverity::MajorOutage,
            'published_at' => now()->subMinutes(15),
        ]);

        $incident->services()->attach($service);
        $incident->components()->attach($component);

        IncidentUpdate::query()->create([
            'incident_id' => $incident->id,
            'title' => 'Investigating',
            'body' => 'The team is investigating elevated error rates.',
            'published_at' => now()->subMinutes(10),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Status Center')
            ->assertSee('API outage')
            ->assertSee('Public API')
            ->assertSee('95.00% uptime');

        $this->get('/incidents/api-outage')
            ->assertOk()
            ->assertSee('Investigating');

        $this->getJson('/api/status/summary')
            ->assertOk()
            ->assertJsonPath('overall_status', ComponentStatus::MajorOutage->value)
            ->assertJsonPath('active_incident_count', 1);

        $this->getJson('/api/status/services')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'API',
                'display_name' => 'Public API',
            ]);

        $this->getJson('/api/status/incidents')
            ->assertOk()
            ->assertJsonFragment([
                'slug' => 'api-outage',
                'title' => 'API outage',
            ]);
    }

    public function test_draft_incidents_are_not_publicly_visible(): void
    {
        $incident = Incident::query()->create([
            'title' => 'Draft incident',
            'slug' => 'draft-incident',
            'status' => IncidentStatus::Draft,
            'severity' => IncidentSeverity::Degraded,
        ]);

        $this->get("/incidents/{$incident->slug}")->assertNotFound();
    }

    public function test_no_data_bars_do_not_report_a_percentage_from_inconsistent_daily_rows(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'uptime_window_days' => 1,
        ]);

        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
            'status' => ComponentStatus::Operational,
            'is_public' => true,
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Cache',
            'status' => ComponentStatus::Operational,
            'automated_status' => ComponentStatus::Operational,
            'is_public' => true,
        ]);

        ComponentDailyUptime::query()->create([
            'component_id' => $component->id,
            'day' => now()->toDateString(),
            'healthy_slots' => 0,
            'observed_slots' => 0,
            'maintenance_slots' => 0,
            'no_data_slots' => 24,
            'uptime_percentage' => 100.00,
        ]);

        $this->getJson('/api/status/services')
            ->assertOk()
            ->assertJsonPath('0.components.0.uptime_bars.0.state', 'no_data')
            ->assertJsonPath('0.components.0.uptime_bars.0.percentage', null);
    }
}
