<?php

use App\Enums\RemoteIntegrationAuthMode;
use App\Enums\RemoteIntegrationSyncMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('remote_app_id')->nullable()->unique();
            $table->string('base_url');
            $table->string('health_url')->nullable();
            $table->string('metadata_url')->nullable();
            $table->string('sync_mode')->default(RemoteIntegrationSyncMode::Hybrid->value);
            $table->string('auth_mode')->default(RemoteIntegrationAuthMode::Bearer->value);
            $table->longText('auth_secret')->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('last_sync_status')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_registration_at')->nullable();
            $table->timestamps();
        });

        Schema::table('components', function (Blueprint $table): void {
            $table->foreignId('remote_integration_id')
                ->nullable()
                ->after('service_id')
                ->constrained('remote_integrations')
                ->nullOnDelete();
            $table->string('remote_component_key')
                ->nullable()
                ->after('remote_integration_id');
            $table->unique(
                ['remote_integration_id', 'remote_component_key'],
                'components_remote_component_unique',
            );
        });

        Schema::table('checks', function (Blueprint $table): void {
            $table->foreignId('remote_integration_id')
                ->nullable()
                ->after('component_id')
                ->constrained('remote_integrations')
                ->nullOnDelete();
            $table->string('remote_component_key')
                ->nullable()
                ->after('remote_integration_id');
            $table->unique(
                ['remote_integration_id', 'remote_component_key'],
                'checks_remote_component_unique',
            );
        });

        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->string('probe_registration_token')
                ->nullable()
                ->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->dropColumn('probe_registration_token');
        });

        Schema::table('checks', function (Blueprint $table): void {
            $table->dropUnique('checks_remote_component_unique');
            $table->dropConstrainedForeignId('remote_integration_id');
            $table->dropColumn('remote_component_key');
        });

        Schema::table('components', function (Blueprint $table): void {
            $table->dropUnique('components_remote_component_unique');
            $table->dropConstrainedForeignId('remote_integration_id');
            $table->dropColumn('remote_component_key');
        });

        Schema::dropIfExists('remote_integrations');
    }
};
