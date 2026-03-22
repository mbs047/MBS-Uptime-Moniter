<?php

namespace Mbs047\LaravelStatusProbe\Commands;

use Illuminate\Console\Command;
use Mbs047\LaravelStatusProbe\Support\HeartbeatRepository;

class HeartbeatCommand extends Command
{
    protected $signature = 'status-probe:heartbeat {subject : Supported values: scheduler}';

    protected $description = 'Write a heartbeat timestamp for an optional status probe contributor.';

    public function handle(HeartbeatRepository $heartbeats): int
    {
        $subject = (string) $this->argument('subject');

        if (! in_array($subject, ['scheduler'], true)) {
            $this->error('Unsupported heartbeat subject.');

            return self::FAILURE;
        }

        $heartbeats->touch($subject);
        $this->info(sprintf('Recorded %s heartbeat.', $subject));

        return self::SUCCESS;
    }
}
