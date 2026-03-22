<?php

namespace App\Filament\Resources\Checks\Pages;

use App\Filament\Resources\Checks\CheckResource;
use App\Services\Checks\CheckConfigValidator;
use Filament\Resources\Pages\CreateRecord;

class CreateCheck extends CreateRecord
{
    protected static string $resource = CheckResource::class;

    protected ?string $subheading = 'Use the wizard to define scope, cadence, connection details, and clear success criteria before saving the check.';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['config'] = $this->normalizeConfig($data['config'] ?? []);
        $data['secret_config'] = $this->normalizeSecretConfig($data['secret_config'] ?? []);
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
}
