<?php

namespace Mbs047\LaravelStatusProbe\Enums;

enum ProbeStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';

    public function rank(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Degraded => 1,
            self::PartialOutage => 2,
            self::MajorOutage => 3,
        };
    }
}
