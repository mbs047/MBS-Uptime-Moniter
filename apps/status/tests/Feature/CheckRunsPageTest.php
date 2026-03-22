<?php

namespace Tests\Feature;

use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Filament\Resources\Checks\CheckResource;
use App\Models\Admin;
use App\Models\Check;
use App\Models\CheckRun;
use App\Models\Component;
use App\Models\Service;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class CheckRunsPageTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_admin_can_view_all_runs_for_a_selected_check(): void
    {
        $admin = Admin::factory()->create();

        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Gateway',
            'is_public' => true,
        ]);

        $check = Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Gateway health',
            'type' => CheckType::Http,
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com/health'],
        ]);

        CheckRun::query()->create([
            'check_id' => $check->id,
            'outcome' => CheckRunOutcome::Passed,
            'severity' => ComponentStatus::Operational,
            'status_code' => 200,
            'latency_ms' => 125,
            'result_payload' => ['summary' => 'Healthy response'],
            'error_payload' => null,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(5)->addSecond(),
        ]);

        CheckRun::query()->create([
            'check_id' => $check->id,
            'outcome' => CheckRunOutcome::HardFailed,
            'severity' => ComponentStatus::MajorOutage,
            'status_code' => 503,
            'latency_ms' => 950,
            'result_payload' => ['summary' => 'Gateway unavailable'],
            'error_payload' => ['message' => 'Connection timed out'],
            'started_at' => now()->subMinute(),
            'finished_at' => now()->subMinute()->addSecond(),
        ]);

        $otherCheck = Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Ignore me',
            'type' => CheckType::Http,
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com/other'],
        ]);

        CheckRun::query()->create([
            'check_id' => $otherCheck->id,
            'outcome' => CheckRunOutcome::Passed,
            'severity' => ComponentStatus::Operational,
            'status_code' => 204,
            'latency_ms' => 50,
            'result_payload' => ['summary' => 'Other check'],
            'error_payload' => null,
            'started_at' => now()->subSeconds(30),
            'finished_at' => now()->subSeconds(29),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(CheckResource::getUrl('runs', ['record' => $check]))
            ->assertOk()
            ->assertSee('Recorded executions')
            ->assertSee('Healthy response')
            ->assertSee('Connection timed out')
            ->assertSee('125 ms')
            ->assertSee('950 ms')
            ->assertDontSee('Other check');
    }
}
