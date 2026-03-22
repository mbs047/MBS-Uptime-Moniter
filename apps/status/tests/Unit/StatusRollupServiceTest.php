<?php

namespace Tests\Unit;

use App\Enums\ComponentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Check;
use App\Models\Component;
use App\Models\Incident;
use App\Models\Service;
use App\Services\Status\StatusRollupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusRollupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_outage_is_derived_when_some_checks_still_pass(): void
    {
        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Gateway',
        ]);

        Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Primary endpoint',
            'type' => 'http',
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com'],
            'latest_severity' => ComponentStatus::MajorOutage,
        ]);

        Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Secondary endpoint',
            'type' => 'http',
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com/secondary'],
            'latest_severity' => ComponentStatus::Operational,
        ]);

        app(StatusRollupService::class)->recalculateComponent($component->id);

        $component->refresh();
        $service->refresh();

        $this->assertSame(ComponentStatus::PartialOutage, $component->automated_status);
        $this->assertSame(ComponentStatus::PartialOutage, $component->status);
        $this->assertSame(ComponentStatus::PartialOutage, $service->status);
    }

    public function test_service_level_maintenance_cascades_to_component_status(): void
    {
        $service = Service::query()->create([
            'name' => 'Auth',
            'slug' => 'auth',
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Token issuer',
        ]);

        $incident = Incident::query()->create([
            'title' => 'Scheduled auth maintenance',
            'slug' => 'auth-maintenance',
            'status' => IncidentStatus::Published,
            'severity' => IncidentSeverity::Maintenance,
            'published_at' => now(),
        ]);

        $incident->services()->attach($service);

        app(StatusRollupService::class)->recalculateComponent($component->id);

        $this->assertSame(ComponentStatus::Maintenance, $component->fresh()->status);
    }
}
