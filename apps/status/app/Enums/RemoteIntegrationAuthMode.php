<?php

namespace App\Enums;

enum RemoteIntegrationAuthMode: string
{
    case Bearer = 'bearer';

    public function label(): string
    {
        return match ($this) {
            self::Bearer => 'Bearer token',
        };
    }
}
