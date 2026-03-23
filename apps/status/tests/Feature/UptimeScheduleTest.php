<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class UptimeScheduleTest extends TestCase
{
    public function test_uptime_refresh_runs_hourly_for_the_current_day_and_nightly_for_recent_days(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $hourlyRefresh = collect($events)->first(function ($event) {
            return $event->expression === '10 * * * *'
                && str_contains($event->command, 'status:refresh-daily-uptime')
                && str_contains($event->command, '--days=1');
        });

        $nightlyRefresh = collect($events)->first(function ($event) {
            return $event->expression === '5 0 * * *'
                && str_contains($event->command, 'status:refresh-daily-uptime')
                && str_contains($event->command, '--days=2');
        });

        $this->assertNotNull($hourlyRefresh, 'Expected an hourly current-day uptime refresh schedule.');
        $this->assertNotNull($nightlyRefresh, 'Expected a nightly two-day uptime refresh schedule.');
    }
}
