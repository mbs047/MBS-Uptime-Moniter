<?php

namespace App\Jobs;

use App\Services\Status\StatusRollupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecalculateComponentStatusJob implements ShouldQueue
{
    use Dispatchable;
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
