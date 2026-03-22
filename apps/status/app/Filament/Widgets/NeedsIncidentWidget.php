<?php

namespace App\Filament\Widgets;

use App\Models\Component;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class NeedsIncidentWidget extends TableWidget
{
    protected static ?string $heading = 'Components needing an incident';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Component::query()
                ->with('service')
                ->where('automated_status', '!=', 'operational')
                ->where('status', '!=', 'maintenance')
                ->whereDoesntHave('incidents', fn (Builder $query) => $query->activeAt(now()))
                ->whereDoesntHave('service.incidents', fn (Builder $query) => $query->activeAt(now()))
                ->orderBy('service_id'))
            ->columns([
                TextColumn::make('service.name')
                    ->label('Service'),
                TextColumn::make('display_name')
                    ->label('Component'),
                TextColumn::make('automated_status')
                    ->badge(),
                TextColumn::make('last_status_changed_at')
                    ->since(),
            ])
            ->paginated([5]);
    }
}
