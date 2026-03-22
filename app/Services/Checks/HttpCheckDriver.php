<?php

namespace App\Services\Checks;

use App\Contracts\Checks\CheckDriver;
use App\Enums\CheckRunOutcome;
use App\Enums\CheckType;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Support\Checks\CheckExecutionResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

class HttpCheckDriver implements CheckDriver
{
    public function type(): CheckType
    {
        return CheckType::Http;
    }

    public function validate(array $config, array $secretConfig = []): array
    {
        return Validator::make([
            'method' => strtoupper((string) ($config['method'] ?? 'GET')),
            'url' => $config['url'] ?? null,
            'headers' => $config['headers'] ?? [],
            'json_body' => $config['json_body'] ?? null,
            'expected_statuses' => array_values(array_map('intval', Arr::wrap($config['expected_statuses'] ?? [200]))),
            'max_latency_ms' => $config['max_latency_ms'] ?? null,
            'text_contains' => $config['text_contains'] ?? null,
            'json_assertions' => array_values($config['json_assertions'] ?? []),
            'auth_type' => $config['auth_type'] ?? null,
            'secret_username' => $secretConfig['username'] ?? null,
            'secret_password' => $secretConfig['password'] ?? null,
            'secret_token' => $secretConfig['token'] ?? null,
        ], [
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS'],
            'url' => ['required', 'url'],
            'headers' => ['array'],
            'expected_statuses' => ['array', 'min:1'],
            'expected_statuses.*' => ['integer', 'between:100,599'],
            'max_latency_ms' => ['nullable', 'integer', 'min:1'],
            'text_contains' => ['nullable', 'string'],
            'json_assertions' => ['array'],
            'json_assertions.*.path' => ['required_with:json_assertions', 'string'],
            'auth_type' => ['nullable', 'in:basic,bearer'],
            'secret_username' => ['required_if:auth_type,basic', 'nullable', 'string'],
            'secret_password' => ['required_if:auth_type,basic', 'nullable', 'string'],
            'secret_token' => ['required_if:auth_type,bearer', 'nullable', 'string'],
        ])->validate();
    }

    public function run(Check $check): CheckExecutionResult
    {
        $config = $this->validate($check->config ?? [], $check->secret_config ?? []);
        $secretConfig = $check->secret_config ?? [];
        $started = microtime(true);

        try {
            $request = Http::timeout($check->timeout_seconds)->withHeaders($config['headers'] ?? []);

            if (($config['auth_type'] ?? null) === 'basic') {
                $request = $request->withBasicAuth(
                    (string) ($secretConfig['username'] ?? ''),
                    (string) ($secretConfig['password'] ?? ''),
                );
            }

            if (($config['auth_type'] ?? null) === 'bearer' && filled($secretConfig['token'] ?? null)) {
                $request = $request->withToken((string) $secretConfig['token']);
            }

            $response = $request->send($config['method'], $config['url'], [
                'json' => $config['json_body'] ?? null,
            ]);
        } catch (Throwable $exception) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                errorPayload: ['message' => $exception->getMessage()],
            );
        }

        $latencyMs = (int) round((microtime(true) - $started) * 1000);
        $payload = [
            'url' => $config['url'],
            'status' => $response->status(),
            'body_excerpt' => str($response->body())->limit(300)->toString(),
        ];

        if (! in_array($response->status(), $config['expected_statuses'], true)) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::HardFailed,
                severity: ComponentStatus::MajorOutage,
                statusCode: $response->status(),
                latencyMs: $latencyMs,
                resultPayload: $payload,
                errorPayload: ['message' => 'Unexpected HTTP status code.'],
            );
        }

        if (($config['max_latency_ms'] ?? null) && $latencyMs > (int) $config['max_latency_ms']) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::SoftFailed,
                severity: ComponentStatus::Degraded,
                statusCode: $response->status(),
                latencyMs: $latencyMs,
                resultPayload: $payload,
                errorPayload: ['message' => 'Latency exceeded configured maximum.'],
            );
        }

        if (filled($config['text_contains'] ?? null) && ! str_contains($response->body(), (string) $config['text_contains'])) {
            return new CheckExecutionResult(
                outcome: CheckRunOutcome::SoftFailed,
                severity: ComponentStatus::Degraded,
                statusCode: $response->status(),
                latencyMs: $latencyMs,
                resultPayload: $payload,
                errorPayload: ['message' => 'Response body did not include required text.'],
            );
        }

        if (! empty($config['json_assertions'])) {
            $decoded = $response->json();

            foreach ($config['json_assertions'] as $assertion) {
                if (data_get($decoded, $assertion['path']) != ($assertion['expected'] ?? null)) {
                    return new CheckExecutionResult(
                        outcome: CheckRunOutcome::SoftFailed,
                        severity: ComponentStatus::Degraded,
                        statusCode: $response->status(),
                        latencyMs: $latencyMs,
                        resultPayload: $payload,
                        errorPayload: ['message' => sprintf('JSON assertion failed for [%s].', $assertion['path'])],
                    );
                }
            }
        }

        return new CheckExecutionResult(
            outcome: CheckRunOutcome::Passed,
            severity: ComponentStatus::Operational,
            statusCode: $response->status(),
            latencyMs: $latencyMs,
            resultPayload: $payload,
        );
    }
}
