<?php

namespace App\Filament\Widgets;

use App\Models\Component;
use App\Models\Incident;
use App\Models\Subscriber;
use App\Services\Status\StatusSummaryService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatusOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $summary = app(StatusSummaryService::class)->summaryPayload();
        $needsIncident = Component::query()
            ->where('automated_status', '!=', 'operational')
            ->where('status', '!=', 'maintenance')
            ->count();

        return [
            Stat::make('Overall status', str($summary['overall_status'])->replace('_', ' ')->title()->toString())
                ->description('Public production state'),
            Stat::make('Active incidents', (string) Incident::query()->published()->whereNull('resolved_at')->count())
                ->description('Published and unresolved'),
            Stat::make('Verified subscribers', (string) Subscriber::query()->activeRecipients()->count())
                ->description('Eligible for incident emails'),
            Stat::make('Needs incident', (string) $needsIncident)
                ->description('Automated failures without maintenance'),
        ];
    }
}
