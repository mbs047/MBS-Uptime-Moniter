<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Status\StatusSummaryService;
use Illuminate\Http\JsonResponse;

class StatusSummaryController extends Controller
{
    public function __construct(
        private readonly StatusSummaryService $statusSummary,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->statusSummary->summaryPayload());
    }
}
