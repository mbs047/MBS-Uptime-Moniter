<?php

namespace App\Jobs;

use App\Enums\CheckRunOutcome;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Models\CheckRun;
use App\Services\Checks\CheckDriverRegistry;
use App\Support\Checks\CheckExecutionResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class RunCheckJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $checkId,
        public readonly bool $manual = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CheckDriverRegistry $registry): void
    {
        $check = Check::query()->with('component.service')->find($this->checkId);

        if (! $check) {
            return;
        }

        if (! $check->enabled && ! $this->manual) {
            return;
        }

        $startedAt = now();

        try {
            $result = $registry->for($check->type)->run($check);
        } catch (Throwable $exception) {
            $result = new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => $exception->getMessage()],
            );
        }

        $check->last_ran_at = $startedAt;
        $check->next_run_at = $startedAt->copy()->addMinutes($check->interval_minutes);
        $check->latest_latency_ms = $result->latencyMs;
        $check->latest_http_status = $result->statusCode;
        $check->latest_error_summary = $result->summary();

        if ($result->outcome === CheckRunOutcome::Passed) {
            $check->consecutive_failures = 0;
            $check->consecutive_recoveries = $check->latest_severity && $check->latest_severity !== ComponentStatus::Operational
                ? $check->consecutive_recoveries + 1
                : 0;
            $check->latest_succeeded_at = now();
        } else {
            $check->consecutive_failures++;
            $check->consecutive_recoveries = 0;
            $check->latest_failed_at = now();
        }

        $previousSeverity = $check->latest_severity ?? ComponentStatus::Operational;
        $effectiveSeverity = match ($result->outcome) {
            CheckRunOutcome::Passed => (
                $previousSeverity !== ComponentStatus::Operational &&
                $check->consecutive_recoveries < $check->recovery_threshold
            )
                ? $previousSeverity
                : ComponentStatus::Operational,
            default => $check->consecutive_failures >= $check->failure_threshold
                ? $result->severity
                : $previousSeverity,
        };

        $check->latest_severity = $effectiveSeverity;
        $check->save();

        CheckRun::query()->create([
            'check_id' => $check->id,
            'outcome' => $result->outcome,
            'severity' => $effectiveSeverity,
            'status_code' => $result->statusCode,
            'latency_ms' => $result->latencyMs,
            'result_payload' => $result->resultPayload,
            'error_payload' => $result->errorPayload,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);

        RecalculateComponentStatusJob::dispatch($check->component_id);
    }
}
