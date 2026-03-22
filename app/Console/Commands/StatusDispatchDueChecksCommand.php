<?php

namespace App\Console\Commands;

use App\Jobs\RunCheckJob;
use App\Models\Check;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('status:dispatch-due-checks')]
#[Description('Dispatch all enabled checks that are due to run')]
class StatusDispatchDueChecksCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = Check::query()
            ->where('enabled', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->orderBy('next_run_at')
            ->get();

        foreach ($checks as $check) {
            RunCheckJob::dispatch($check->id);
        }

        $this->info(sprintf('Dispatched %d due checks.', $checks->count()));

        return self::SUCCESS;
    }
}
