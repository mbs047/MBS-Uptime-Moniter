<?php

namespace App\Services\Checks;

use App\Enums\CheckType;

class CheckConfigValidator
{
    public function __construct(
        private readonly CheckDriverRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $secretConfig
     * @return array<string, mixed>
     */
    public function validate(CheckType|string $type, array $config, array $secretConfig = []): array
    {
        return $this->registry->for($type)->validate($config, $secretConfig);
    }
}
