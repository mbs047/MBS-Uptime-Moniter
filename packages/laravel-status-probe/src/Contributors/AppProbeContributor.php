<?php

namespace Mbs047\LaravelStatusProbe\Contributors;

use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

class AppProbeContributor implements ProbeContributor
{
    public function key(): string
    {
        return 'app';
    }

    public function label(): string
    {
        return 'Application';
    }

    public function description(): ?string
    {
        return 'Laravel application runtime bootstrap health.';
    }

    public function defaultCheckConfig(): array
    {
        return [];
    }

    public function resolve(): ProbeResult
    {
        return new ProbeResult(
            ProbeStatus::Operational,
            'Laravel application booted successfully.',
        );
    }
}
