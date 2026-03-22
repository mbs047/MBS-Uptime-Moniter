<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        PlatformSetting::query()->firstOrCreate([], [
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility for critical services',
            'seo_title' => 'Status Center',
            'seo_description' => 'Production health, incidents, and availability history.',
            'uptime_window_days' => 90,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
            'probe_registration_token' => null,
        ]);
    }
}
