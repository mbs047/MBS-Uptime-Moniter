<?php

namespace App\Filament\Widgets;

use App\Models\Check;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FailingChecksWidget extends TableWidget
{
    protected static ?string $heading = 'Failing checks';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Check::query()
                ->with('component')
                ->where('latest_severity', '!=', 'operational')
                ->orderByDesc('last_ran_at'))
            ->columns([
                TextColumn::make('component.display_name')
                    ->label('Component'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('latest_severity')
                    ->badge(),
                TextColumn::make('latest_error_summary')
                    ->limit(48)
                    ->wrap(),
                TextColumn::make('last_ran_at')
                    ->since(),
            ])
            ->paginated([5]);
    }
}
