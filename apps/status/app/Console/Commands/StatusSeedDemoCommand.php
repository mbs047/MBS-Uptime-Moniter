<?php

namespace App\Console\Commands;

use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Check;
use App\Models\CheckRun;
use App\Models\Component;
use App\Models\ComponentDailyUptime;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\PlatformSetting;
use App\Models\Service;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('status:seed-demo {--days=90 : Number of trailing days of uptime history to create}')]
#[Description('Create a colorful demo monitoring dataset without touching non-demo records')]
class StatusSeedDemoCommand extends Command
{
    private const string DemoSlugPrefix = 'demo-seed-';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $windowDays = max(14, (int) $this->option('days'));

        $this->seedPlatformSettings($windowDays);
        $this->clearDemoDataset();

        $seededServices = collect($this->demoServiceDefinitions())
            ->map(fn (array $definition) => $this->seedService($definition, $windowDays));

        $this->seedIncidents($seededServices->all());

        $serviceIds = $seededServices->pluck('id');
        $componentIds = Component::query()->whereIn('service_id', $serviceIds)->pluck('id');
        $checkIds = Check::query()->whereIn('component_id', $componentIds)->pluck('id');

        $this->info(sprintf(
            'Seeded demo dataset: %d services, %d components, %d checks, %d check runs, %d uptime rows, %d incidents.',
            $serviceIds->count(),
            $componentIds->count(),
            $checkIds->count(),
            CheckRun::query()->whereIn('check_id', $checkIds)->count(),
            ComponentDailyUptime::query()->whereIn('component_id', $componentIds)->count(),
            Incident::query()->where('slug', 'like', self::DemoSlugPrefix.'%')->count(),
        ));

        $this->line('Demo-only records were refreshed in place. Existing real monitoring data was preserved.');
        $this->line('Visit the public status page and history page to review green, yellow, orange, red, blue, and gray uptime bars.');

        return self::SUCCESS;
    }

    protected function seedPlatformSettings(int $windowDays): void
    {
        $settings = PlatformSetting::query()->firstOrCreate([], [
            'brand_name' => 'Status Center',
            'brand_tagline' => 'Operational visibility for critical services',
            'seo_title' => 'Status Center',
            'seo_description' => 'Production health, incidents, and availability history.',
            'uptime_window_days' => $windowDays,
            'raw_run_retention_days' => 14,
            'default_failure_threshold' => 2,
            'default_recovery_threshold' => 1,
        ]);

        if (($settings->uptime_window_days ?? 0) < $windowDays) {
            $settings->forceFill(['uptime_window_days' => $windowDays])->save();
        }
    }

    protected function clearDemoDataset(): void
    {
        Incident::query()
            ->where('slug', 'like', self::DemoSlugPrefix.'%')
            ->get()
            ->each
            ->delete();

        Service::query()
            ->where('slug', 'like', self::DemoSlugPrefix.'%')
            ->get()
            ->each
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function seedService(array $definition, int $windowDays): Service
    {
        $service = Service::query()->create([
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'description' => $definition['description'],
            'sort_order' => $definition['sort_order'],
            'status' => $definition['status'],
            'is_public' => true,
            'last_status_changed_at' => now()->subMinutes(20),
        ]);

        foreach ($definition['components'] as $index => $componentDefinition) {
            $component = Component::query()->create([
                'service_id' => $service->id,
                'display_name' => $componentDefinition['display_name'],
                'description' => $componentDefinition['description'],
                'sort_order' => $index + 1,
                'status' => $componentDefinition['status'],
                'automated_status' => $componentDefinition['status'],
                'is_public' => true,
                'last_status_changed_at' => now()->subMinutes(15),
            ]);

            $check = Check::query()->create([
                'component_id' => $component->id,
                'name' => $componentDefinition['check']['name'],
                'type' => $componentDefinition['check']['type'],
                'interval_minutes' => 1,
                'timeout_seconds' => 10,
                'failure_threshold' => 2,
                'recovery_threshold' => 1,
                'enabled' => true,
                'config' => $componentDefinition['check']['config'],
                'next_run_at' => now()->addMinute(),
            ]);

            $this->seedCheckRuns($check, $componentDefinition['runs']);
            $this->seedComponentHistory($component, $windowDays, $componentDefinition['history']);
        }

        return $service;
    }

    /**
     * @param  array<int, array<string, mixed>>  $runs
     */
    protected function seedCheckRuns(Check $check, array $runs): void
    {
        $latestRun = null;

        foreach ($runs as $runDefinition) {
            $startedAt = now()->subMinutes($runDefinition['minutes_ago']);

            $run = CheckRun::query()->create([
                'check_id' => $check->id,
                'outcome' => $runDefinition['outcome'],
                'severity' => $runDefinition['severity'],
                'status_code' => $runDefinition['status_code'],
                'latency_ms' => $runDefinition['latency_ms'],
                'result_payload' => ['summary' => $runDefinition['summary']],
                'error_payload' => $runDefinition['error_message'] ? ['message' => $runDefinition['error_message']] : null,
                'started_at' => $startedAt,
                'finished_at' => $startedAt->copy()->addSeconds(1),
            ]);

            if ($latestRun === null || $run->started_at->gt($latestRun->started_at)) {
                $latestRun = $run;
            }
        }

        if (! $latestRun) {
            return;
        }

        $check->forceFill([
            'last_ran_at' => $latestRun->started_at,
            'next_run_at' => $latestRun->started_at->copy()->addMinutes($check->interval_minutes),
            'latest_severity' => $latestRun->severity,
            'latest_error_summary' => data_get($latestRun->error_payload, 'message')
                ?? data_get($latestRun->result_payload, 'summary'),
            'latest_latency_ms' => $latestRun->latency_ms,
            'latest_http_status' => $latestRun->status_code,
            'latest_succeeded_at' => $latestRun->outcome === CheckRunOutcome::Passed ? $latestRun->finished_at : null,
            'latest_failed_at' => $latestRun->outcome === CheckRunOutcome::Passed ? null : $latestRun->finished_at,
            'consecutive_failures' => $latestRun->outcome === CheckRunOutcome::Passed ? 0 : 1,
            'consecutive_recoveries' => $latestRun->outcome === CheckRunOutcome::Passed ? 1 : 0,
        ])->save();
    }

    /**
     * @param  array<int, string>  $historyOverrides
     */
    protected function seedComponentHistory(Component $component, int $windowDays, array $historyOverrides): void
    {
        foreach (range($windowDays - 1, 0) as $offset) {
            $state = $historyOverrides[$offset] ?? 'operational';
            $metrics = $this->dailyMetricsForState($state);
            $day = Carbon::today(config('app.timezone'))->subDays($offset)->toDateString();

            ComponentDailyUptime::query()->updateOrCreate(
                [
                    'component_id' => $component->id,
                    'day' => $day,
                ],
                [
                    'healthy_slots' => $metrics['healthy_slots'],
                    'observed_slots' => $metrics['observed_slots'],
                    'maintenance_slots' => $metrics['maintenance_slots'],
                    'no_data_slots' => $metrics['no_data_slots'],
                    'uptime_percentage' => $metrics['uptime_percentage'],
                ],
            );
        }
    }

    /**
     * @return array{healthy_slots:int, observed_slots:int, maintenance_slots:int, no_data_slots:int, uptime_percentage:float}
     */
    protected function dailyMetricsForState(string $state): array
    {
        return match ($state) {
            'degraded' => [
                'healthy_slots' => 99,
                'observed_slots' => 100,
                'maintenance_slots' => 0,
                'no_data_slots' => 0,
                'uptime_percentage' => 99.00,
            ],
            'partial_outage' => [
                'healthy_slots' => 97,
                'observed_slots' => 100,
                'maintenance_slots' => 0,
                'no_data_slots' => 0,
                'uptime_percentage' => 97.00,
            ],
            'major_outage' => [
                'healthy_slots' => 82,
                'observed_slots' => 100,
                'maintenance_slots' => 0,
                'no_data_slots' => 0,
                'uptime_percentage' => 82.00,
            ],
            'maintenance' => [
                'healthy_slots' => 0,
                'observed_slots' => 0,
                'maintenance_slots' => 100,
                'no_data_slots' => 0,
                'uptime_percentage' => 0,
            ],
            'no_data' => [
                'healthy_slots' => 0,
                'observed_slots' => 0,
                'maintenance_slots' => 0,
                'no_data_slots' => 100,
                'uptime_percentage' => 0,
            ],
            default => [
                'healthy_slots' => 100,
                'observed_slots' => 100,
                'maintenance_slots' => 0,
                'no_data_slots' => 0,
                'uptime_percentage' => 100.00,
            ],
        };
    }

    /**
     * @param  list<Service>  $services
     */
    protected function seedIncidents(array $services): void
    {
        $servicesBySlug = collect($services)->keyBy('slug');
        $components = Component::query()
            ->whereIn('service_id', $servicesBySlug->pluck('id'))
            ->get()
            ->keyBy(fn (Component $component) => "{$component->service->slug}:{$component->display_name}");

        $incidents = [
            [
                'title' => 'Cache latency spike',
                'slug' => self::DemoSlugPrefix.'cache-latency-spike',
                'summary' => 'A burst of cache contention increased response times for a subset of requests.',
                'severity' => IncidentSeverity::Degraded,
                'days_ago' => 14,
                'duration_hours' => 3,
                'service_slug' => self::DemoSlugPrefix.'core-api',
                'component_key' => self::DemoSlugPrefix.'core-api:Cache',
                'update_title' => 'Performance stabilized',
                'update_body' => 'Cache latency returned to baseline after rebalancing the hot shard.',
            ],
            [
                'title' => 'Database maintenance window',
                'slug' => self::DemoSlugPrefix.'database-maintenance',
                'summary' => 'Planned maintenance rotated the primary database connection during a scheduled window.',
                'severity' => IncidentSeverity::Maintenance,
                'days_ago' => 5,
                'duration_hours' => 2,
                'service_slug' => self::DemoSlugPrefix.'core-api',
                'component_key' => self::DemoSlugPrefix.'core-api:Database',
                'update_title' => 'Maintenance completed',
                'update_body' => 'Primary database maintenance completed successfully and all connections were restored.',
            ],
            [
                'title' => 'DNS resolution outage',
                'slug' => self::DemoSlugPrefix.'dns-resolution-outage',
                'summary' => 'DNS resolution intermittently failed for public edge lookups.',
                'severity' => IncidentSeverity::MajorOutage,
                'days_ago' => 28,
                'duration_hours' => 4,
                'service_slug' => self::DemoSlugPrefix.'edge-delivery',
                'component_key' => self::DemoSlugPrefix.'edge-delivery:DNS Resolution',
                'update_title' => 'Root cause identified',
                'update_body' => 'DNS traffic was restored after the provider rolled back the faulty zone propagation.',
            ],
        ];

        foreach ($incidents as $definition) {
            $service = $servicesBySlug->get($definition['service_slug']);
            $component = $components->get($definition['component_key']);
            $startedAt = now()->subDays($definition['days_ago'])->startOfDay()->addHours(9);
            $resolvedAt = $startedAt->copy()->addHours($definition['duration_hours']);

            $incident = Incident::query()->create([
                'title' => $definition['title'],
                'slug' => $definition['slug'],
                'summary' => $definition['summary'],
                'status' => IncidentStatus::Published,
                'severity' => $definition['severity'],
                'starts_at' => $startedAt,
                'scheduled_starts_at' => $definition['severity'] === IncidentSeverity::Maintenance ? $startedAt : null,
                'scheduled_ends_at' => $definition['severity'] === IncidentSeverity::Maintenance ? $resolvedAt : null,
                'resolved_at' => $resolvedAt,
                'published_at' => $startedAt,
            ]);

            if ($service) {
                $incident->services()->sync([$service->id]);
            }

            if ($component) {
                $incident->components()->sync([$component->id]);
            }

            IncidentUpdate::query()->create([
                'incident_id' => $incident->id,
                'title' => $definition['update_title'],
                'body' => $definition['update_body'],
                'status' => IncidentStatus::Resolved,
                'published_at' => $resolvedAt->copy()->subMinutes(10),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function demoServiceDefinitions(): array
    {
        return [
            [
                'name' => 'Demo Core API',
                'slug' => self::DemoSlugPrefix.'core-api',
                'description' => 'Synthetic production traffic for previewing the public status experience.',
                'sort_order' => 1,
                'status' => ComponentStatus::Degraded,
                'components' => [
                    [
                        'display_name' => 'Application',
                        'description' => 'Laravel application runtime bootstrap health.',
                        'status' => ComponentStatus::Operational,
                        'check' => [
                            'name' => 'Application health endpoint',
                            'type' => CheckType::Http,
                            'config' => ['method' => 'GET', 'url' => 'https://demo.example.com/health'],
                        ],
                        'runs' => [
                            ['minutes_ago' => 35, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 124, 'summary' => 'Healthy response', 'error_message' => null],
                            ['minutes_ago' => 20, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 131, 'summary' => 'Healthy response', 'error_message' => null],
                            ['minutes_ago' => 10, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 119, 'summary' => 'Healthy response', 'error_message' => null],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 117, 'summary' => 'Healthy response', 'error_message' => null],
                        ],
                        'history' => [
                            11 => 'degraded',
                            18 => 'partial_outage',
                            31 => 'major_outage',
                        ],
                    ],
                    [
                        'display_name' => 'Cache',
                        'description' => 'Configured cache store read and write cycle.',
                        'status' => ComponentStatus::Degraded,
                        'check' => [
                            'name' => 'Cache TCP reachability',
                            'type' => CheckType::Tcp,
                            'config' => ['host' => 'cache.demo.example.com', 'port' => 6379],
                        ],
                        'runs' => [
                            ['minutes_ago' => 32, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 44, 'summary' => 'TCP connection succeeded', 'error_message' => null],
                            ['minutes_ago' => 15, 'outcome' => CheckRunOutcome::SoftFailed, 'severity' => ComponentStatus::Degraded, 'status_code' => null, 'latency_ms' => 680, 'summary' => 'Cache latency exceeded threshold', 'error_message' => 'Latency threshold exceeded'],
                            ['minutes_ago' => 8, 'outcome' => CheckRunOutcome::SoftFailed, 'severity' => ComponentStatus::Degraded, 'status_code' => null, 'latency_ms' => 701, 'summary' => 'Cache latency exceeded threshold', 'error_message' => 'Latency threshold exceeded'],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::SoftFailed, 'severity' => ComponentStatus::Degraded, 'status_code' => null, 'latency_ms' => 655, 'summary' => 'Cache latency exceeded threshold', 'error_message' => 'Latency threshold exceeded'],
                        ],
                        'history' => [
                            2 => 'degraded',
                            6 => 'partial_outage',
                            14 => 'major_outage',
                            22 => 'degraded',
                        ],
                    ],
                    [
                        'display_name' => 'Database',
                        'description' => 'Primary database connection round trip.',
                        'status' => ComponentStatus::Operational,
                        'check' => [
                            'name' => 'Database TCP reachability',
                            'type' => CheckType::Tcp,
                            'config' => ['host' => 'db.demo.example.com', 'port' => 3306],
                        ],
                        'runs' => [
                            ['minutes_ago' => 26, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 28, 'summary' => 'TCP connection succeeded', 'error_message' => null],
                            ['minutes_ago' => 12, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 31, 'summary' => 'TCP connection succeeded', 'error_message' => null],
                            ['minutes_ago' => 5, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 29, 'summary' => 'TCP connection succeeded', 'error_message' => null],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 27, 'summary' => 'TCP connection succeeded', 'error_message' => null],
                        ],
                        'history' => [
                            5 => 'maintenance',
                            27 => 'partial_outage',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Demo Edge Delivery',
                'slug' => self::DemoSlugPrefix.'edge-delivery',
                'description' => 'Synthetic edge infrastructure and background job delivery status.',
                'sort_order' => 2,
                'status' => ComponentStatus::Operational,
                'components' => [
                    [
                        'display_name' => 'Public Web',
                        'description' => 'TLS edge and public website availability.',
                        'status' => ComponentStatus::Operational,
                        'check' => [
                            'name' => 'Public edge certificate',
                            'type' => CheckType::Ssl,
                            'config' => ['host' => 'www.demo.example.com', 'port' => 443, 'minimum_days_remaining' => 14],
                        ],
                        'runs' => [
                            ['minutes_ago' => 24, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 42, 'summary' => 'TLS handshake succeeded', 'error_message' => null],
                            ['minutes_ago' => 14, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 39, 'summary' => 'TLS handshake succeeded', 'error_message' => null],
                            ['minutes_ago' => 6, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 37, 'summary' => 'TLS handshake succeeded', 'error_message' => null],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 36, 'summary' => 'TLS handshake succeeded', 'error_message' => null],
                        ],
                        'history' => [
                            19 => 'degraded',
                            20 => 'degraded',
                        ],
                    ],
                    [
                        'display_name' => 'DNS Resolution',
                        'description' => 'Authoritative DNS record resolution for the edge domain.',
                        'status' => ComponentStatus::Operational,
                        'check' => [
                            'name' => 'Edge DNS lookup',
                            'type' => CheckType::Dns,
                            'config' => ['host' => 'demo.example.com', 'record_type' => 'A'],
                        ],
                        'runs' => [
                            ['minutes_ago' => 30, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 18, 'summary' => 'DNS lookup succeeded', 'error_message' => null],
                            ['minutes_ago' => 18, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 17, 'summary' => 'DNS lookup succeeded', 'error_message' => null],
                            ['minutes_ago' => 7, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 19, 'summary' => 'DNS lookup succeeded', 'error_message' => null],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => null, 'latency_ms' => 18, 'summary' => 'DNS lookup succeeded', 'error_message' => null],
                        ],
                        'history' => [
                            28 => 'major_outage',
                            41 => 'degraded',
                        ],
                    ],
                    [
                        'display_name' => 'Queue Worker',
                        'description' => 'Background job intake and heartbeat visibility.',
                        'status' => ComponentStatus::Operational,
                        'check' => [
                            'name' => 'Worker heartbeat endpoint',
                            'type' => CheckType::Http,
                            'config' => ['method' => 'GET', 'url' => 'https://demo.example.com/queue-health'],
                        ],
                        'runs' => [
                            ['minutes_ago' => 27, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 103, 'summary' => 'Worker heartbeat acknowledged', 'error_message' => null],
                            ['minutes_ago' => 16, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 108, 'summary' => 'Worker heartbeat acknowledged', 'error_message' => null],
                            ['minutes_ago' => 9, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 101, 'summary' => 'Worker heartbeat acknowledged', 'error_message' => null],
                            ['minutes_ago' => 1, 'outcome' => CheckRunOutcome::Passed, 'severity' => ComponentStatus::Operational, 'status_code' => 200, 'latency_ms' => 98, 'summary' => 'Worker heartbeat acknowledged', 'error_message' => null],
                        ],
                        'history' => [
                            0 => 'no_data',
                            1 => 'no_data',
                            2 => 'no_data',
                            3 => 'degraded',
                            9 => 'partial_outage',
                        ],
                    ],
                ],
            ],
        ];
    }
}
