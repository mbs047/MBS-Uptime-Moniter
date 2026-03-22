<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminInvites\AdminInviteResource;
use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Checks\CheckResource;
use App\Filament\Resources\Components\ComponentResource;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Admin;
use App\Models\AdminInvite;
use App\Models\Check;
use App\Models\CheckRun;
use App\Models\Component;
use App\Models\Incident;
use App\Models\PlatformSetting;
use App\Models\RemoteIntegration;
use App\Models\Service;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_preserves_old_check_runs(): void
    {
        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Gateway',
        ]);

        $check = Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Gateway health',
            'type' => 'http',
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com/health'],
        ]);

        CheckRun::query()->create([
            'check_id' => $check->id,
            'outcome' => 'hard_failed',
            'severity' => 'degraded',
            'status_code' => 503,
            'latency_ms' => 950,
            'result_payload' => ['ok' => false],
            'error_payload' => ['message' => 'timeout'],
            'started_at' => now()->subDays(30),
            'finished_at' => now()->subDays(30)->addSecond(),
        ]);

        $this->artisan('status:prune-check-runs')
            ->expectsOutputToContain('Automatic pruning is disabled')
            ->assertSuccessful();

        $this->assertDatabaseCount('check_runs', 1);
    }

    public function test_filament_resources_do_not_allow_deletion(): void
    {
        $resources = [
            [AdminInviteResource::class, new AdminInvite],
            [AdminResource::class, new Admin],
            [CheckResource::class, new Check],
            [ComponentResource::class, new Component],
            [IncidentResource::class, new Incident],
            [PlatformSettingResource::class, new PlatformSetting],
            [RemoteIntegrationResource::class, new RemoteIntegration],
            [ServiceResource::class, new Service],
            [SubscriberResource::class, new Subscriber],
        ];

        foreach ($resources as [$resourceClass, $model]) {
            $this->assertFalse($resourceClass::canDeleteAny());
            $this->assertFalse($resourceClass::canDelete($model));
        }
    }
}
