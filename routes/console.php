<?php

use App\Console\Commands\StatusDispatchDueChecksCommand;
use App\Console\Commands\StatusPruneCheckRunsCommand;
use App\Console\Commands\StatusRefreshDailyUptimeCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(StatusDispatchDueChecksCommand::class)->everyMinute();
Schedule::command(StatusRefreshDailyUptimeCommand::class, ['--days' => 2])->dailyAt('00:05');
Schedule::command(StatusPruneCheckRunsCommand::class)->dailyAt('00:15');
