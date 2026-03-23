<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentDailyUptime;
use App\Models\Incident;
use App\Models\Service;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class StatusSeedDemoCommandTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_demo_seed_command_creates_a_colorful_status_dataset_without_duplication(): void
    {
        $realService = Service::query()->create([
            'name' => 'Real API',
            'slug' => 'real-api',
        ]);

        $this->artisan('status:seed-demo', ['--days' => 30])
            ->expectsOutputToContain('Seeded demo dataset')
            ->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $realService->id, 'slug' => 'real-api']);
        $this->assertSame(2, Service::query()->where('slug', 'like', 'demo-seed-%')->count());
        $this->assertSame(6, Component::query()->whereHas('service', fn ($query) => $query->where('slug', 'like', 'demo-seed-%'))->count());
        $this->assertSame(180, ComponentDailyUptime::query()->whereHas('component.service', fn ($query) => $query->where('slug', 'like', 'demo-seed-%'))->count());
        $this->assertSame(3, Incident::query()->where('slug', 'like', 'demo-seed-%')->count());

        $this->assertDatabaseHas('component_daily_uptimes', [
            'maintenance_slots' => 100,
            'observed_slots' => 0,
        ]);

        $this->assertDatabaseHas('component_daily_uptimes', [
            'no_data_slots' => 100,
            'observed_slots' => 0,
        ]);

        $this->assertDatabaseHas('component_daily_uptimes', [
            'uptime_percentage' => 99.00,
            'observed_slots' => 100,
        ]);

        $this->artisan('status:seed-demo', ['--days' => 30])->assertSuccessful();

        $this->assertSame(2, Service::query()->where('slug', 'like', 'demo-seed-%')->count());
        $this->assertSame(6, Component::query()->whereHas('service', fn ($query) => $query->where('slug', 'like', 'demo-seed-%'))->count());
        $this->assertSame(180, ComponentDailyUptime::query()->whereHas('component.service', fn ($query) => $query->where('slug', 'like', 'demo-seed-%'))->count());
        $this->assertSame(3, Incident::query()->where('slug', 'like', 'demo-seed-%')->count());
    }
}
