<?php

use App\Enums\ComponentStatus;
use App\Enums\IncidentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_invites', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default(ComponentStatus::Operational->value);
            $table->boolean('is_public')->default(true);
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default(ComponentStatus::Operational->value);
            $table->string('automated_status')->default(ComponentStatus::Operational->value);
            $table->boolean('is_public')->default(true);
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('interval_minutes');
            $table->unsignedInteger('timeout_seconds');
            $table->unsignedInteger('failure_threshold')->default(2);
            $table->unsignedInteger('recovery_threshold')->default(1);
            $table->boolean('enabled')->default(true);
            $table->json('config');
            $table->longText('secret_config')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_ran_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('consecutive_recoveries')->default(0);
            $table->string('latest_severity')->nullable();
            $table->string('latest_error_summary')->nullable();
            $table->unsignedInteger('latest_latency_ms')->nullable();
            $table->unsignedSmallInteger('latest_http_status')->nullable();
            $table->timestamp('latest_succeeded_at')->nullable();
            $table->timestamp('latest_failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('check_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained()->cascadeOnDelete();
            $table->string('outcome');
            $table->string('severity')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('result_payload')->nullable();
            $table->json('error_payload')->nullable();
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable();

            $table->index(['check_id', 'started_at']);
        });

        Schema::create('component_daily_uptimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('healthy_slots')->default(0);
            $table->unsignedInteger('observed_slots')->default(0);
            $table->unsignedInteger('maintenance_slots')->default(0);
            $table->unsignedInteger('no_data_slots')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(0);

            $table->unique(['component_id', 'day']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('status')->default(IncidentStatus::Draft->value);
            $table->string('severity');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('scheduled_starts_at')->nullable();
            $table->timestamp('scheduled_ends_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('incident_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['incident_id', 'service_id']);
        });

        Schema::create('component_incident', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->unique(['incident_id', 'component_id']);
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('verification_token')->unique()->nullable();
            $table->string('unsubscribe_token')->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_confirmation_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->default('Status Center');
            $table->string('brand_tagline')->nullable();
            $table->string('brand_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->unsignedInteger('uptime_window_days')->default(90);
            $table->unsignedInteger('raw_run_retention_days')->default(14);
            $table->unsignedInteger('default_failure_threshold')->default(2);
            $table->unsignedInteger('default_recovery_threshold')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('component_incident');
        Schema::dropIfExists('incident_service');
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('component_daily_uptimes');
        Schema::dropIfExists('check_runs');
        Schema::dropIfExists('checks');
        Schema::dropIfExists('components');
        Schema::dropIfExists('services');
        Schema::dropIfExists('admin_invites');
    }
};
