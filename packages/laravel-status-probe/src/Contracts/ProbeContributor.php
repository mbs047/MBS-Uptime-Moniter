<?php

namespace Mbs047\LaravelStatusProbe\Contracts;

use Mbs047\LaravelStatusProbe\ProbeResult;

interface ProbeContributor
{
    public function key(): string;

    public function label(): string;

    public function description(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function defaultCheckConfig(): array;

    public function resolve(): ProbeResult;
}
