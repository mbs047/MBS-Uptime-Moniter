<?php

namespace App\Console\Commands;

use App\Models\CheckRun;
use App\Models\PlatformSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('status:prune-check-runs')]
#[Description('Prune raw check run rows older than the configured retention window')]
class StatusPruneCheckRunsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = PlatformSetting::current();
        $threshold = now()->subDays($settings->raw_run_retention_days ?: 14);

        $deleted = CheckRun::query()
            ->where('started_at', '<', $threshold)
            ->delete();

        $this->info(sprintf('Pruned %d check runs older than %s.', $deleted, $threshold->toDateTimeString()));

        return self::SUCCESS;
    }
}
