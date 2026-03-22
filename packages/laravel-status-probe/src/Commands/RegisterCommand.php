<?php

namespace Mbs047\LaravelStatusProbe\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Mbs047\LaravelStatusProbe\Support\MetadataPayloadFactory;

class RegisterCommand extends Command
{
    protected $signature = 'status-probe:register';

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

        if (! filled($monitorUrl) || ! filled($monitorToken)) {
            $this->error('STATUS_MONITOR_URL and STATUS_MONITOR_TOKEN must be configured before registering.');

            return self::FAILURE;
        }

        Http::timeout(10)
            ->withToken($monitorToken)
            ->acceptJson()
            ->post($monitorUrl.'/api/integrations/probes/register', $this->payloads->make(includeSecret: true))
            ->throw();

        $this->info('Status probe registration pushed successfully.');

        return self::SUCCESS;
    }
}
