<?php

namespace App\Services\Checks;

use App\Contracts\Checks\CheckDriver;
use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Support\Checks\CheckExecutionResult;
use Illuminate\Support\Facades\Validator;

class SslCheckDriver implements CheckDriver
{
    public function type(): CheckType
    {
        return CheckType::Ssl;
    }

    public function validate(array $config, array $secretConfig = []): array
    {
        return Validator::make([
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? 443,
            'minimum_days_remaining' => $config['minimum_days_remaining'] ?? 14,
        ], [
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'minimum_days_remaining' => ['required', 'integer', 'min:0'],
        ])->validate();
    }

    public function run(Check $check): CheckExecutionResult
    {
        $config = $this->validate($check->config ?? []);
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'peer_name' => $config['host'],
            ],
        ]);

        $client = @stream_socket_client(
            sprintf('ssl://%s:%d', $config['host'], $config['port']),
            $errorCode,
            $errorMessage,
            $check->timeout_seconds,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if (! $client) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => $errorMessage ?: 'SSL handshake failed.'],
            );
        }

        $params = stream_context_get_params($client);
        $certificate = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? null);
        fclose($client);

        if (! $certificate || empty($certificate['validTo_time_t'])) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => 'Unable to parse the peer certificate.'],
            );
        }

        $daysRemaining = (int) floor(($certificate['validTo_time_t'] - now()->timestamp) / 86400);

        if ($daysRemaining < (int) $config['minimum_days_remaining']) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::SoftFailed,
                severity: ComponentStatus::Degraded,
                resultPayload: ['days_remaining' => $daysRemaining],
                errorPayload: ['message' => 'Certificate expiry threshold breached.'],
            );
        }

        return new CheckExecutionResult(
            outcome: CheckRunOutcome::Passed,
            severity: ComponentStatus::Operational,
            resultPayload: ['days_remaining' => $daysRemaining],
        );
    }
}
