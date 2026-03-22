<?php

namespace App\Support\Filament;

use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class FormActions
{
    public static function makeGenerateSlugAction(string $sourceField, string $targetField = 'slug'): Action
    {
        return Action::make('generate_'.$targetField.'_from_'.str_replace('.', '_', $sourceField))
            ->icon(Heroicon::OutlinedSparkles)
            ->tooltip('Generate slug from the source field')
            ->action(function (Get $get, Set $set) use ($sourceField, $targetField): void {
                $sourceValue = trim((string) ($get($sourceField) ?? ''));

                if (blank($sourceValue)) {
                    return;
                }

                $set($targetField, Str::slug($sourceValue));
            });
    }
}
