<?php

namespace App\Enums;

enum ComponentStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';
    case Maintenance = 'maintenance';

    public function rank(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Degraded => 1,
            self::PartialOutage => 2,
            self::MajorOutage => 3,
            self::Maintenance => 4,
        };
    }

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }

    public function cssToken(): string
    {
        return match ($this) {
            self::Operational => 'operational',
            self::Degraded => 'degraded',
            self::PartialOutage => 'partial',
            self::MajorOutage => 'major',
            self::Maintenance => 'maintenance',
        };
    }
}
