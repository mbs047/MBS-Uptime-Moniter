<?php

namespace Tests\Feature;

use App\Jobs\RunCheckJob;
use App\Models\Check;
use App\Models\Component;
use App\Models\Service;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class StatusDispatchDueChecksTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_due_checks_are_dispatched_and_future_checks_are_skipped(): void
    {
        Queue::fake();

        $service = Service::query()->create([
            'name' => 'API',
            'slug' => 'api',
        ]);

        $component = Component::query()->create([
            'service_id' => $service->id,
            'display_name' => 'Gateway',
        ]);

        $dueCheck = Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Due check',
            'type' => 'http',
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com'],
            'next_run_at' => now()->subMinute(),
        ]);

        Check::query()->create([
            'component_id' => $component->id,
            'name' => 'Future check',
            'type' => 'http',
            'interval_minutes' => 1,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'recovery_threshold' => 1,
            'enabled' => true,
            'config' => ['url' => 'https://example.com/future'],
            'next_run_at' => now()->addMinute(),
        ]);

        $this->artisan('status:dispatch-due-checks')->assertSuccessful();

        Queue::assertPushed(RunCheckJob::class, 1);
        Queue::assertPushed(RunCheckJob::class, fn (RunCheckJob $job) => $job->checkId === $dueCheck->id);
    }
}
