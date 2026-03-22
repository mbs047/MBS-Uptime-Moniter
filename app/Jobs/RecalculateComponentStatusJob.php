<?php

namespace App\Jobs;

use App\Services\Status\StatusRollupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateComponentStatusJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $componentId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(StatusRollupService $rollups): void
    {
        $rollups->recalculateComponent($this->componentId);
    }
}
