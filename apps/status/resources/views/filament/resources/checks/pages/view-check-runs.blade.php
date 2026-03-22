<x-filament-panels::page>
    <x-filament::section
        heading="Recorded executions"
        description="Each run is written once and preserved, so operators can compare recent failures against the current check configuration without losing older evidence."
    >
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
