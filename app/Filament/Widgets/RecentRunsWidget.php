<?php

namespace App\Filament\Widgets;

use App\Models\CheckRun;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentRunsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent check runs';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CheckRun::query()
                ->with('check.component')
                ->latest('started_at'))
            ->columns([
                TextColumn::make('check.component.display_name')
                    ->label('Component'),
                TextColumn::make('check.name')
                    ->label('Check'),
                TextColumn::make('outcome')
                    ->badge(),
                TextColumn::make('severity')
                    ->badge(),
                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->suffix(' ms'),
                TextColumn::make('started_at')
                    ->since(),
            ])
            ->paginated([10]);
    }
}
