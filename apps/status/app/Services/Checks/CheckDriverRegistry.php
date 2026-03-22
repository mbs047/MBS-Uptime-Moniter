<?php

namespace App\Services\Checks;

use App\Contracts\Checks\CheckDriver;
use App\Enums\CheckType;
use InvalidArgumentException;

class CheckDriverRegistry
{
    /**
     * @param  array<int, CheckDriver>  $drivers
     */
    public function __construct(
        private readonly array $drivers,
    ) {}

    public function for(CheckType | string $type): CheckDriver
    {
        $value = $type instanceof CheckType ? $type->value : $type;

        foreach ($this->drivers as $driver) {
            if ($driver->type()->value === $value) {
                return $driver;
            }
        }

        throw new InvalidArgumentException(sprintf('Unsupported check driver [%s].', $value));
    }
}
