<?php

namespace Mbs047\LaravelStatusProbe;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mbs047\LaravelStatusProbe\Commands\HeartbeatCommand;
use Mbs047\LaravelStatusProbe\Commands\InstallCommand;
use Mbs047\LaravelStatusProbe\Commands\RegisterCommand;
use Mbs047\LaravelStatusProbe\Contributors\AppProbeContributor;
use Mbs047\LaravelStatusProbe\Contributors\CacheProbeContributor;
use Mbs047\LaravelStatusProbe\Contributors\DatabaseProbeContributor;
use Mbs047\LaravelStatusProbe\Contributors\QueueProbeContributor;
use Mbs047\LaravelStatusProbe\Contributors\SchedulerProbeContributor;
use Mbs047\LaravelStatusProbe\Http\Controllers\HealthController;
use Mbs047\LaravelStatusProbe\Http\Controllers\MetadataController;
use Mbs047\LaravelStatusProbe\Http\Middleware\AuthorizeProbeRequest;
use Mbs047\LaravelStatusProbe\Support\HeartbeatRepository;
use Mbs047\LaravelStatusProbe\Support\HealthPayloadFactory;
use Mbs047\LaravelStatusProbe\Support\MetadataPayloadFactory;
use Mbs047\LaravelStatusProbe\Support\ProbeManager;

class StatusProbeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/status-probe.php', 'status-probe');

        $this->app->singleton(HeartbeatRepository::class);
        $this->app->singleton(ProbeManager::class);
        $this->app->singleton(HealthPayloadFactory::class);
        $this->app->singleton(MetadataPayloadFactory::class);
        $this->app->singleton(AppProbeContributor::class);
        $this->app->singleton(DatabaseProbeContributor::class);
        $this->app->singleton(CacheProbeContributor::class);
        $this->app->singleton(QueueProbeContributor::class);
        $this->app->singleton(SchedulerProbeContributor::class);

        $contributors = [];

        if (config('status-probe.contributors.app', true)) {
            $contributors[] = AppProbeContributor::class;
        }

        if (config('status-probe.contributors.db', true)) {
            $contributors[] = DatabaseProbeContributor::class;
        }

        if (config('status-probe.contributors.cache', true)) {
            $contributors[] = CacheProbeContributor::class;
        }

        if (config('status-probe.contributors.queue', false)) {
            $contributors[] = QueueProbeContributor::class;
        }

        if (config('status-probe.contributors.scheduler', false)) {
            $contributors[] = SchedulerProbeContributor::class;
        }

        if ($contributors !== []) {
            $this->app->tag($contributors, 'status-probe.contributors');
        }
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerQueueHeartbeat();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/status-probe.php' => config_path('status-probe.php'),
            ], 'status-probe-config');

            $this->commands([
                InstallCommand::class,
                RegisterCommand::class,
                HeartbeatCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $middleware = array_merge((array) config('status-probe.middleware', ['api']), [
            AuthorizeProbeRequest::class,
        ]);

        Route::middleware($middleware)->group(function (): void {
            Route::get(ltrim((string) config('status-probe.health_path', 'status/health'), '/'), HealthController::class)
                ->name('status-probe.health');
            Route::get(ltrim((string) config('status-probe.metadata_path', 'status/metadata'), '/'), MetadataController::class)
                ->name('status-probe.metadata');
        });
    }

    protected function registerQueueHeartbeat(): void
    {
        if (! config('status-probe.contributors.queue', false)) {
            return;
        }

        Queue::looping(function (): void {
            $this->app->make(HeartbeatRepository::class)->touch('queue');
        });
    }
}
