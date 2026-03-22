<?php

namespace App\Services\Checks;

use App\Contracts\Checks\CheckDriver;
use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Support\Checks\CheckExecutionResult;
use Illuminate\Support\Facades\Validator;

class DnsCheckDriver implements CheckDriver
{
    public function type(): CheckType
    {
        return CheckType::Dns;
    }

    public function validate(array $config, array $secretConfig = []): array
    {
        return Validator::make([
            'host' => $config['host'] ?? null,
            'record_type' => strtoupper((string) ($config['record_type'] ?? 'A')),
            'expected_values' => array_values($config['expected_values'] ?? []),
        ], [
            'host' => ['required', 'string'],
            'record_type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS'],
            'expected_values' => ['array'],
        ])->validate();
    }

    public function run(Check $check): CheckExecutionResult
    {
        $config = $this->validate($check->config ?? []);
        $records = @dns_get_record($config['host'], constant('DNS_'.$config['record_type']));

        if (! $records || count($records) === 0) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => 'No DNS records resolved.'],
            );
        }

        $values = collect($records)->map(function (array $record): string {
            return (string) ($record['ip'] ?? $record['ipv6'] ?? $record['target'] ?? $record['txt'] ?? $record['mname'] ?? '');
        })->filter()->values()->all();

        if (! empty($config['expected_values'])) {
            $missing = collect($config['expected_values'])->diff($values)->values()->all();

            if ($missing !== []) {
                return new CheckExecutionResult(
                    outcome: CheckRunOutcome::SoftFailed,
                    severity: ComponentStatus::Degraded,
                    resultPayload: ['resolved_values' => $values],
                    errorPayload: ['message' => 'Resolved records did not match the expected values.'],
                );
            }
        }

        return new CheckExecutionResult(
            outcome: CheckRunOutcome::Passed,
            severity: ComponentStatus::Operational,
            resultPayload: ['resolved_values' => $values],
        );
    }
}
