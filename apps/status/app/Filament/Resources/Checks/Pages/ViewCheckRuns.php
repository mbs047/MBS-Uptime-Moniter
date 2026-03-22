<?php

namespace App\Filament\Resources\Checks\Pages;

use App\Enums\CheckRunOutcome;
use App\Enums\ComponentStatus;
use App\Filament\Resources\Checks\CheckResource;
use App\Models\CheckRun;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ViewCheckRuns extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static ?string $breadcrumb = 'Runs';

    protected static string $resource = CheckResource::class;

    protected string $view = 'filament.resources.checks.pages.view-check-runs';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        $this->mountInteractsWithTable();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Check runs';
    }

    public function getSubheading(): ?string
    {
        return 'Review the append-only execution log for this check, including outcome, severity, latency, and any recorded failure summary.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CheckRun::query()
                ->whereBelongsTo($this->getRecord())
                ->latest('started_at'))
            ->columns([
                TextColumn::make('outcome')
                    ->badge()
                    ->color(fn (?CheckRunOutcome $state): string => match ($state) {
                        CheckRunOutcome::Passed => 'success',
                        CheckRunOutcome::SoftFailed => 'warning',
                        CheckRunOutcome::HardFailed => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?CheckRunOutcome $state): ?string => $state ? str($state->value)->replace('_', ' ')->title()->toString() : null),
                TextColumn::make('severity')
                    ->badge()
                    ->color(fn (?ComponentStatus $state): string => match ($state) {
                        ComponentStatus::Operational => 'success',
                        ComponentStatus::Degraded, ComponentStatus::PartialOutage => 'warning',
                        ComponentStatus::MajorOutage => 'danger',
                        ComponentStatus::Maintenance => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?ComponentStatus $state): ?string => $state?->label()),
                TextColumn::make('status_code')
                    ->label('HTTP')
                    ->placeholder('n/a'),
                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->formatStateUsing(fn (?int $state): ?string => filled($state) ? "{$state} ms" : null)
                    ->placeholder('n/a'),
                TextColumn::make('summary')
                    ->state(fn (CheckRun $record): string => data_get($record->error_payload, 'message')
                        ?? data_get($record->result_payload, 'message')
                        ?? data_get($record->result_payload, 'summary')
                        ?? 'No summary recorded')
                    ->wrap()
                    ->limit(90),
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, Y g:i:s a')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime('M j, Y g:i:s a')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->paginated([25, 50, 100])
            ->poll('15s')
            ->emptyStateHeading('No runs recorded yet')
            ->emptyStateDescription('Run the check manually or wait for the scheduler to dispatch the next due execution.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('view_check')
                    ->label('View check')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url($this->getResourceUrl('view')),
                Action::make('edit_check')
                    ->label('Edit check')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url($this->getResourceUrl('edit'))
                    ->visible(fn (): bool => static::getResource()::canEdit($this->getRecord())),
                CheckResource::makeRunNowAction(),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button(),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
}
