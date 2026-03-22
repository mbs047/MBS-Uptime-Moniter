<?php

namespace Tests\Unit;

use App\Enums\CheckRunOutcome;
use App\Enums\ComponentStatus;
use App\Models\Check;
use App\Services\Checks\HttpCheckDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class HttpCheckDriverTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_basic_auth_checks_require_username_and_password(): void
    {
        $driver = new HttpCheckDriver;

        $this->expectException(ValidationException::class);

        $driver->validate([
            'method' => 'GET',
            'url' => 'https://example.com',
            'auth_type' => 'basic',
        ], []);
    }

    public function test_bearer_auth_checks_require_a_token(): void
    {
        $driver = new HttpCheckDriver;

        $this->expectException(ValidationException::class);

        $driver->validate([
            'method' => 'GET',
            'url' => 'https://example.com',
            'auth_type' => 'bearer',
        ], []);
    }

    public function test_http_check_validation_accepts_complete_auth_config(): void
    {
        $driver = new HttpCheckDriver;

        $validated = $driver->validate([
            'method' => 'GET',
            'url' => 'https://example.com',
            'auth_type' => 'basic',
        ], [
            'username' => 'ops',
            'password' => 'secret',
        ]);

        $this->assertSame('basic', $validated['auth_type']);
    }

    public function test_http_checks_can_map_remote_status_from_a_json_path(): void
    {
        Http::fake([
            'https://example.com/status/health' => Http::response([
                'checks' => [
                    'db' => ['status' => 'partial_outage'],
                ],
            ]),
        ]);

        $driver = new HttpCheckDriver;
        $check = Check::query()->make([
            'type' => 'http',
            'timeout_seconds' => 10,
            'config' => [
                'method' => 'GET',
                'url' => 'https://example.com/status/health',
                'expected_statuses' => [200],
                'status_json_path' => 'checks.db.status',
            ],
        ]);

        $result = $driver->run($check);

        $this->assertSame(CheckRunOutcome::HardFailed, $result->outcome);
        $this->assertSame(ComponentStatus::PartialOutage, $result->severity);
    }

    public function test_http_checks_fail_when_a_status_json_path_is_missing(): void
    {
        Http::fake([
            'https://example.com/status/health' => Http::response([
                'checks' => [
                    'db' => ['status' => 'operational'],
                ],
            ]),
        ]);

        $driver = new HttpCheckDriver;
        $check = Check::query()->make([
            'type' => 'http',
            'timeout_seconds' => 10,
            'config' => [
                'method' => 'GET',
                'url' => 'https://example.com/status/health',
                'expected_statuses' => [200],
                'status_json_path' => 'checks.cache.status',
            ],
        ]);

        $result = $driver->run($check);

        $this->assertSame(CheckRunOutcome::HardFailed, $result->outcome);
        $this->assertSame(ComponentStatus::MajorOutage, $result->severity);
    }
}
