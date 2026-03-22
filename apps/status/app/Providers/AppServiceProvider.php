<?php

namespace App\Providers;

use App\Contracts\Checks\CheckDriver;
use App\Services\Checks\CheckDriverRegistry;
use App\Services\Checks\DnsCheckDriver;
use App\Services\Checks\HttpCheckDriver;
use App\Services\Checks\SslCheckDriver;
use App\Services\Checks\TcpCheckDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CheckDriverRegistry::class, function () {
            return new CheckDriverRegistry([
                new HttpCheckDriver,
                new SslCheckDriver,
                new DnsCheckDriver,
                new TcpCheckDriver,
            ]);
        });

        $this->app->bind(CheckDriver::class, HttpCheckDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
