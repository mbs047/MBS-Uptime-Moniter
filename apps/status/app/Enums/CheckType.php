<?php

namespace App\Enums;

enum CheckType: string
{
    case Http = 'http';
    case Ssl = 'ssl';
    case Dns = 'dns';
    case Tcp = 'tcp';

    public function label(): string
    {
        return match ($this) {
            self::Http => 'HTTP',
            self::Ssl => 'SSL',
            self::Dns => 'DNS',
            self::Tcp => 'TCP',
        };
    }
}
