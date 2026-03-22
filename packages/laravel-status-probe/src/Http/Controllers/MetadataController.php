<?php

namespace Mbs047\LaravelStatusProbe\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Mbs047\LaravelStatusProbe\Support\MetadataPayloadFactory;

class MetadataController
{
    public function __construct(
        protected readonly MetadataPayloadFactory $payloads,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->payloads->make());
    }
}
