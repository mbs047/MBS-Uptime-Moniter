<?php

namespace Mbs047\LaravelStatusProbe\Contributors;

use Illuminate\Support\Facades\DB;
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

class DatabaseProbeContributor implements ProbeContributor
{
    public function key(): string
    {
        return 'db';
    }

    public function label(): string
    {
        return 'Database';
    }

    public function description(): ?string
    {
        return 'Primary database connection round trip.';
    }

    public function defaultCheckConfig(): array
    {
        return [];
    }

    public function resolve(): ProbeResult
    {
        try {
            DB::select('select 1');

            return new ProbeResult(
                ProbeStatus::Operational,
                'Database query round trip succeeded.',
            );
        } catch (\Throwable $exception) {
            return new ProbeResult(
                ProbeStatus::MajorOutage,
                $exception->getMessage(),
            );
        }
    }
}
