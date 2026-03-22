<?php

namespace Mbs047\LaravelStatusProbe\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'status-probe:install';

    protected $description = 'Publish the status probe config and seed the required environment values.';

    public function handle(Filesystem $files): int
    {
        $configTarget = config_path('status-probe.php');
        $configSource = dirname(__DIR__, 2).'/config/status-probe.php';

        if (! $files->exists($configTarget)) {
            $files->copy($configSource, $configTarget);
            $this->info('Published config/status-probe.php');
        }

        $this->ensureEnvironmentValue($files, 'STATUS_PROBE_APP_ID', (string) Str::uuid());
        $this->ensureEnvironmentValue($files, 'STATUS_PROBE_TOKEN', Str::random(40));
        $this->ensureEnvironmentValue($files, 'STATUS_PROBE_HEALTH_PATH', 'status/health');
        $this->ensureEnvironmentValue($files, 'STATUS_PROBE_METADATA_PATH', 'status/metadata');
        $this->ensureEnvironmentValue($files, 'STATUS_MONITOR_URL', '');
        $this->ensureEnvironmentValue($files, 'STATUS_MONITOR_TOKEN', '');

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Set STATUS_MONITOR_URL and STATUS_MONITOR_TOKEN in your .env file.');
        $this->line('2. Run php artisan status-probe:register to push this app into the monitor.');
        $this->line('3. If scheduler monitoring is enabled, add php artisan status-probe:heartbeat scheduler to the scheduler.');

        return self::SUCCESS;
    }

    protected function ensureEnvironmentValue(Filesystem $files, string $key, string $value): void
    {
        $envPath = app()->environmentFilePath();
        $contents = $files->exists($envPath) ? $files->get($envPath) : '';

        if (str_contains($contents, $key.'=')) {
            return;
        }

        $files->append($envPath, PHP_EOL.$key.'='.$value);
        $this->info(sprintf('Added %s to %s', $key, basename($envPath)));
    }
}
