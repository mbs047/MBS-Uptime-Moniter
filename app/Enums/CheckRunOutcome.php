<?php

namespace App\Enums;

enum CheckRunOutcome: string
{
    case Passed = 'passed';
    case SoftFailed = 'soft_failed';
    case HardFailed = 'hard_failed';

    public function isFailure(): bool
    {
        return $this !== self::Passed;
    }
}
