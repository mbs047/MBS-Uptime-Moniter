<?php

namespace Mbs047\LaravelStatusProbe\Support;

use Illuminate\Contracts\Container\Container;
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;

class ProbeManager
{
    public function __construct(
        protected readonly Container $container,
    ) {}

    /**
     * @return array<int, ProbeContributor>
     */
    public function contributors(): array
    {
        /** @var array<int, ProbeContributor> $contributors */
        $contributors = iterator_to_array($this->container->tagged('status-probe.contributors'), false);

        usort($contributors, fn (ProbeContributor $left, ProbeContributor $right) => strcmp($left->key(), $right->key()));

        return $contributors;
    }
}
