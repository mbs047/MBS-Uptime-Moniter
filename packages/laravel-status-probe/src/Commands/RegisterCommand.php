<?php

namespace Mbs047\LaravelStatusProbe\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Mbs047\LaravelStatusProbe\Support\MetadataPayloadFactory;
use Mbs047\LaravelStatusProbe\Support\MonitorHttpOptions;

class RegisterCommand extends Command
{
    protected $signature = 'status-probe:register
        {--insecure : Disable TLS certificate verification for this registration request only}';

    protected $description = 'Push this application registration payload to the remote status monitor.';

    public function __construct(
        protected MetadataPayloadFactory $payloads,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $monitorUrl = rtrim((string) config('status-probe.monitor.url'), '/');
        $monitorToken = (string) config('status-probe.monitor.token');
        $timeoutSeconds = (int) config('status-probe.monitor.timeout_seconds', 10);

        if (! filled($monitorUrl) || ! filled($monitorToken)) {
            $this->error('STATUS_MONITOR_URL and STATUS_MONITOR_TOKEN must be configured before registering.');

            return self::FAILURE;
        }

        try {
            Http::timeout($timeoutSeconds)
                ->withOptions(MonitorHttpOptions::make(insecure: (bool) $this->option('insecure')))
                ->withToken($monitorToken)
                ->acceptJson()
                ->post($monitorUrl.'/api/integrations/probes/register', $this->payloads->make(includeSecret: true))
                ->throw();
        } catch (ConnectionException $exception) {
            $this->error('Unable to connect to the status monitor.');
            $this->line($exception->getMessage());

            if (str_starts_with($monitorUrl, 'https://')) {
                $this->newLine();
                $this->warn('If this monitor uses local or self-signed HTTPS, try one of these options:');
                $this->line('- rerun with: php artisan status-probe:register --insecure');
                $this->line('- set STATUS_MONITOR_VERIFY=false in .env');
                $this->line('- or set STATUS_MONITOR_CA_PATH=/path/to/local-ca.pem');
            }

            return self::FAILURE;
        }

        $this->info('Status probe registration pushed successfully.');

        return self::SUCCESS;
    }
}
