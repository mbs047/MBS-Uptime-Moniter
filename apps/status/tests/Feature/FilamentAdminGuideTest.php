<?php

namespace Tests\Feature;

use App\Filament\AdminDashboard;
use App\Filament\Pages\ConnectLaravelAppsGuide;
use App\Models\Admin;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_quick_actions_dropdown(): void
    {
        $admin = Admin::factory()->create();

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(AdminDashboard::getUrl(panel: 'admin'));

        $response
            ->assertOk()
            ->assertSee('Quick actions')
            ->assertSee('Dashboard');
    }

    public function test_connection_guide_shows_copy_ready_install_and_push_details(): void
    {
        config(['app.url' => 'https://status.example.test']);

        $admin = Admin::factory()->create();

        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility',
            'probe_registration_token' => 'monitor-token-123',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(ConnectLaravelAppsGuide::getUrl(panel: 'admin'));

        $response
            ->assertOk()
            ->assertSee('https://packagist.org/packages/mbs047/laravel-status-probe')
            ->assertSee('composer require mbs047/laravel-status-probe')
            ->assertSee('php artisan status-probe:register')
            ->assertSee('STATUS_MONITOR_URL=https://status.example.test')
            ->assertSee('STATUS_MONITOR_TOKEN=monitor-token-123');
    }

    public function test_connection_guide_explains_when_push_registration_token_is_missing(): void
    {
        $admin = Admin::factory()->create();

        PlatformSetting::query()->create([
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get(ConnectLaravelAppsGuide::getUrl(panel: 'admin'));

        $response
            ->assertOk()
            ->assertSee('Push registration is waiting for a monitor token')
            ->assertSee('Open monitor settings');
    }
}
