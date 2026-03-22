<?php

namespace Mbs047\LaravelStatusProbe\Support;

class MonitorHttpOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function make(bool $insecure = false): array
    {
        if ($insecure) {
            return ['verify' => false];
        }

        $caPath = config('status-probe.monitor.ca_path');

        if (filled($caPath)) {
            return ['verify' => $caPath];
        }

        return [
            'verify' => (bool) config('status-probe.monitor.verify', true),
        ];
    }
}
