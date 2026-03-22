<?php

namespace App\Enums;

enum RemoteIntegrationSyncMode: string
{
    case Pull = 'pull';
    case Push = 'push';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return str($this->value)->title()->toString();
    }
}
