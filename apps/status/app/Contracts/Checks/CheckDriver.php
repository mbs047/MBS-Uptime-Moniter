<?php

namespace App\Contracts\Checks;

use App\Enums\CheckType;
use App\Models\Check;
use App\Support\Checks\CheckExecutionResult;

interface CheckDriver
{
    public function type(): CheckType;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $secretConfig
     * @return array<string, mixed>
     */
    public function validate(array $config, array $secretConfig = []): array;

    public function run(Check $check): CheckExecutionResult;
}
