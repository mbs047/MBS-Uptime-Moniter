<?php

namespace App\Filament\Resources\Checks\Pages;

use App\Filament\Resources\Checks\CheckResource;
use App\Services\Checks\CheckConfigValidator;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCheck extends EditRecord
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['config']['json_body']) && is_array($data['config']['json_body'])) {
            $data['config']['json_body'] = json_encode($data['config']['json_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        // Stored secrets stay encrypted at rest and are never pushed back into the edit form.
        $data['secret_config'] = [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['config'] = $this->normalizeConfig($data['config'] ?? []);
        $data['secret_config'] = $this->mergeSecretConfig(
            $data['config'] ?? [],
            $this->normalizeSecretConfig($data['secret_config'] ?? []),
        );
        $data['config'] = app(CheckConfigValidator::class)->validate($data['type'], $data['config'], $data['secret_config']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function normalizeConfig(array $config): array
    {
        if (filled($config['json_body'] ?? null) && is_string($config['json_body'])) {
            $decoded = json_decode($config['json_body'], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $config['json_body'] = $decoded;
            }
        }

        return array_filter($config, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $secretConfig
     * @return array<string, mixed>
     */
    protected function normalizeSecretConfig(array $secretConfig): array
    {
        return array_filter($secretConfig, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $secretConfig
     * @return array<string, mixed>
     */
    protected function mergeSecretConfig(array $config, array $secretConfig): array
    {
        $existingSecrets = $this->record->secret_config ?? [];
        $authType = $config['auth_type'] ?? null;

        return match ($authType) {
            'basic' => array_merge(
                array_intersect_key($existingSecrets, array_flip(['username', 'password'])),
                array_intersect_key($secretConfig, array_flip(['username', 'password'])),
            ),
            'bearer' => array_merge(
                array_intersect_key($existingSecrets, ['token' => true]),
                array_intersect_key($secretConfig, ['token' => true]),
            ),
            default => [],
        };
    }
}
