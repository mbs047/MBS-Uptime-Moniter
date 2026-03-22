<?php

namespace App\Enums;

enum RemoteIntegrationSyncStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
