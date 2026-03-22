<?php

namespace App\Console\Commands;

use App\Models\CheckRun;
use App\Models\PlatformSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('status:prune-check-runs')]
#[Description('Report raw check run rows older than the configured retention window without deleting them')]
class StatusPruneCheckRunsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = PlatformSetting::current();
        $threshold = now()->subDays($settings->raw_run_retention_days ?: 14);

        $matchingRuns = CheckRun::query()
            ->where('started_at', '<', $threshold)
            ->count();

        $this->warn(sprintf(
            'Automatic pruning is disabled. %d check runs are older than %s and were preserved.',
            $matchingRuns,
            $threshold->toDateTimeString(),
        ));

        return self::SUCCESS;
    }
}
