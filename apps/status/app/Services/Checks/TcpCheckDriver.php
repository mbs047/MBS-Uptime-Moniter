<?php

namespace App\Services\Checks;

use App\Contracts\Checks\CheckDriver;
use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Support\Checks\CheckExecutionResult;
use Illuminate\Support\Facades\Validator;

class TcpCheckDriver implements CheckDriver
{
    public function type(): CheckType
    {
        return CheckType::Tcp;
    }

    public function validate(array $config, array $secretConfig = []): array
    {
        return Validator::make([
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
        ], [
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'between:1,65535'],
        ])->validate();
    }

    public function run(Check $check): CheckExecutionResult
    {
        $config = $this->validate($check->config ?? []);
        $started = microtime(true);

        $socket = @fsockopen(
            $config['host'],
            (int) $config['port'],
            $errorCode,
            $errorMessage,
            $check->timeout_seconds,
        );

        if (! $socket) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => $errorMessage ?: 'TCP connection failed.'],
            );
        }

        fclose($socket);

        return new CheckExecutionResult(
            outcome: CheckRunOutcome::Passed,
            severity: ComponentStatus::Operational,
            latencyMs: (int) round((microtime(true) - $started) * 1000),
        );
    }
}
