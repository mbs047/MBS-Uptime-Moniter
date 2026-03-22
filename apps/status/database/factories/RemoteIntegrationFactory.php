<?php

namespace Database\Factories;

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use App\Models\RemoteIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemoteIntegration>
 */
class RemoteIntegrationFactory extends Factory
{
    protected $model = RemoteIntegration::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'remote_app_id' => fake()->unique()->slug(),
            'base_url' => fake()->url(),
            'health_url' => fake()->url().'/status/health',
            'metadata_url' => fake()->url().'/status/metadata',
            'sync_mode' => RemoteIntegrationSyncMode::Hybrid,
            'auth_mode' => RemoteIntegrationAuthMode::Bearer,
            'auth_secret' => 'probe-token',
            'tls_verify' => true,
        ];
    }
}
