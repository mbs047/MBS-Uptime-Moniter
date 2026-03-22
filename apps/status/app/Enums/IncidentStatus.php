<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Resolved = 'resolved';

    public function label(): string
    {
        return str($this->value)->title()->toString();
    }
}
