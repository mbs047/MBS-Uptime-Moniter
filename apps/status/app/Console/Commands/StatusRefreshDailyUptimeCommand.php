<?php

namespace App\Console\Commands;

use App\Services\Status\DailyUptimeAggregator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('status:refresh-daily-uptime {--days=1 : Number of trailing days to refresh including today}')]
#[Description('Refresh component daily uptime rows for the trailing number of days')]
class StatusRefreshDailyUptimeCommand extends Command
{
    public function __construct(
        private readonly DailyUptimeAggregator $aggregator,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        foreach (range(0, $days - 1) as $dayOffset) {
            $this->aggregator->refreshForDay(now()->subDays($dayOffset));
        }

        $this->info(sprintf('Refreshed uptime rows for %d day(s).', $days));

        return self::SUCCESS;
    }
}
