<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case Degraded = 'degraded';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }

    public function toComponentStatus(): ComponentStatus
    {
        return ComponentStatus::from($this->value);
    }
}
