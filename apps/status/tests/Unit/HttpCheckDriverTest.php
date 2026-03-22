<?php

namespace Tests\Unit;

use App\Services\Checks\HttpCheckDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HttpCheckDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_auth_checks_require_username_and_password(): void
    {
        $driver = new HttpCheckDriver();

        $this->expectException(ValidationException::class);

        $driver->validate([
            'method' => 'GET',
            'url' => 'https://example.com',
            'auth_type' => 'basic',
        ], []);
    }

    public function test_bearer_auth_checks_require_a_token(): void
    {
        $driver = new HttpCheckDriver();

        $this->expectException(ValidationException::class);

        $driver->validate([
            'method' => 'GET',
            'url' => 'https://example.com',
            'auth_type' => 'bearer',
        ], []);
    }

    public function test_http_check_validation_accepts_complete_auth_config(): void
    {
        $driver = new HttpCheckDriver();

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
}
