<?php

namespace Tests\Unit;

use App\Filament\Resources\RemoteIntegrations\RemoteIntegrationResource;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RemoteIntegrationResourceTest extends TestCase
{
    public function test_tls_sync_failures_include_local_https_guidance(): void
    {
        $message = RemoteIntegrationResource::describeSyncFailure(
            new RuntimeException('cURL error 35: TLS connect error for https://mbs-saas.test/status/metadata'),
        );

        $this->assertStringContainsString('cURL error 35: TLS connect error', $message);
        $this->assertStringContainsString('disable Verify TLS certificates', $message);
        $this->assertStringContainsString('Custom CA bundle path', $message);
    }

    public function test_non_tls_sync_failures_are_left_unchanged(): void
    {
        $message = RemoteIntegrationResource::describeSyncFailure(
            new RuntimeException('HTTP request returned status code 500'),
        );

        $this->assertSame('HTTP request returned status code 500', $message);
    }
}
