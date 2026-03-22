<?php

namespace Mbs047\LaravelStatusProbe\Support;

use Illuminate\Support\Str;
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

class HealthPayloadFactory
{
    public function __construct(
        protected readonly ProbeManager $manager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function make(): array
    {
        $checks = [];

        foreach ($this->manager->contributors() as $contributor) {
            $result = $this->safeResolve($contributor);

            $checks[$contributor->key()] = [
                'label' => $contributor->label(),
                'description' => $contributor->description(),
                'status' => $result->status->value,
                'summary' => $result->summary,
                'details' => (object) $result->details,
            ];
        }

        $overallStatus = collect($checks)
            ->pluck('status')
            ->map(fn (string $status) => ProbeStatus::from($status))
            ->sortByDesc(fn (ProbeStatus $status) => $status->rank())
            ->first() ?? ProbeStatus::Operational;

        return [
            'overall_status' => $overallStatus->value,
            'generated_at' => now()->toIso8601String(),
            'service' => [
                'name' => config('status-probe.service_name', config('app.name', 'Laravel Application')),
                'slug' => config('status-probe.service_slug') ?: Str::slug((string) config('status-probe.service_name', config('app.name', 'Laravel Application'))),
                'description' => config('status-probe.service_description'),
            ],
            'checks' => $checks,
        ];
    }

    protected function safeResolve(ProbeContributor $contributor): ProbeResult
    {
        try {
            return $contributor->resolve();
        } catch (\Throwable $exception) {
            return new ProbeResult(
                ProbeStatus::MajorOutage,
                $exception->getMessage(),
                ['exception' => $exception::class],
            );
        }
    }
}
