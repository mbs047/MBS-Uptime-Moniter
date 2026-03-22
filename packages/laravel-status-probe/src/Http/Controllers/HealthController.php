<?php

namespace Mbs047\LaravelStatusProbe\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Mbs047\LaravelStatusProbe\Support\HealthPayloadFactory;

class HealthController
{
    public function __construct(
        protected readonly HealthPayloadFactory $payloads,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->payloads->make());
    }
}
