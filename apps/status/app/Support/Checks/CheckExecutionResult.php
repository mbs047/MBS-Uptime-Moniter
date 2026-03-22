<?php

namespace App\Support\Checks;

use App\Enums\CheckRunOutcome;
use App\Enums\ComponentStatus;

readonly class CheckExecutionResult
{
    /**
     * @param  array<string, mixed>  $resultPayload
     * @param  array<string, mixed>  $errorPayload
     */
    public function __construct(
        public CheckRunOutcome $outcome,
        public ComponentStatus $severity,
        public ?int $statusCode = null,
        public ?int $latencyMs = null,
        public array $resultPayload = [],
        public array $errorPayload = [],
    ) {}

    public function summary(): ?string
    {
        return $this->errorPayload['message'] ?? null;
    }
}
