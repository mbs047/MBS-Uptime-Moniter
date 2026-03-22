<?php

namespace App\Http\Controllers\PublicStatus;

use App\Http\Controllers\Controller;
use App\Services\Status\StatusSummaryService;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    public function __construct(
        private readonly StatusSummaryService $statusSummary,
    ) {}

    public function __invoke(): View
    {
        return view('status.index', $this->statusSummary->overviewPayload());
    }
}
