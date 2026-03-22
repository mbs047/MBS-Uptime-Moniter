<?php

namespace Mbs047\LaravelStatusProbe;

use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;

readonly class ProbeResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public ProbeStatus $status,
        public ?string $summary = null,
        public array $details = [],
    ) {}
}
