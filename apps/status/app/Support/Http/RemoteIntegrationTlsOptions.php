<?php

namespace App\Support\Http;

use App\Models\RemoteIntegration;

class RemoteIntegrationTlsOptions
{
    /**
     * @return array{verify?: bool|string}
     */
    public static function for(RemoteIntegration $integration): array
    {
        if ($integration->tls_verify === false) {
            return ['verify' => false];
        }

        if (filled($integration->tls_ca_path)) {
            return ['verify' => $integration->tls_ca_path];
        }

        return [];
    }
}
