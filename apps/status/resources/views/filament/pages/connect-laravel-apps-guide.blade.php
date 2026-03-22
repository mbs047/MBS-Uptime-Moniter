@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-panels::page>
    <div style="display: grid; gap: 1.25rem;">
        @unless ($hasPushToken)
            <x-filament::section
                heading="Push registration is waiting for a monitor token"
                description="Remote Laravel apps can still be added with pull sync, but push registration will stay unavailable until this monitor has its own registration token."
                :icon="Heroicon::OutlinedKey"
            >
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                        <x-filament::badge color="warning" :icon="Heroicon::OutlinedArrowUpOnSquare">
                            Push registration disabled
                        </x-filament::badge>

                        <x-filament::badge color="gray" :icon="Heroicon::OutlinedArrowDownTray">
                            Pull sync still available
                        </x-filament::badge>
                    </div>

                    <div
                        style="
                            display: flex;
                            align-items: flex-start;
                            justify-content: space-between;
                            gap: 1rem;
                            flex-wrap: wrap;
                            padding: 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(245, 158, 11, 0.25);
                            background: rgba(255, 251, 235, 0.8);
                        "
                    >
                        <div style="display: grid; gap: 0.5rem; max-width: 48rem;">
                            <strong style="font-size: 0.95rem;">Set the monitor token before asking a remote app to run <code>php artisan status-probe:register</code>.</strong>
                            <span style="font-size: 0.9rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                Once the token exists, the remote package can post its metadata payload to this monitor and create the linked integration automatically.
                            </span>
                        </div>

                        <x-filament::button
                            :href="$settingsUrl"
                            tag="a"
                            color="warning"
                            :icon="Heroicon::OutlinedCog6Tooth"
                        >
                            Open monitor settings
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endunless

        <div
            style="
                display: grid;
                gap: 1.25rem;
                grid-template-columns: minmax(0, 1fr);
            "
        >
            <div style="display: grid; gap: 1.25rem;">
                <x-filament::section
                    heading="Recommended onboarding flow"
                    description="Use the package to expose authenticated health and metadata endpoints, then let this monitor import the service, components, and package-managed checks without manual JSON-path mapping."
                    :icon="Heroicon::OutlinedBookOpen"
                >
                    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));">
                        @foreach ($flowSteps as $step)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: rgba(250, 250, 249, 1);
                                "
                            >
                                <x-filament::badge color="warning" :icon="Heroicon::OutlinedSparkles">
                                    {{ $step['badge'] }}
                                </x-filament::badge>

                                <div style="display: grid; gap: 0.45rem;">
                                    <strong>{{ $step['title'] }}</strong>
                                    <span style="font-size: 0.92rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                        {{ $step['body'] }}
                                    </span>
                                </div>

                                @if ($loop->first)
                                    <div style="display: grid; gap: 0.75rem; margin-top: 0.25rem;">
                                        <x-filament::button
                                            :href="$packagePackagistUrl"
                                            tag="a"
                                            target="_blank"
                                            color="gray"
                                            outlined
                                            :icon="Heroicon::OutlinedArrowTopRightOnSquare"
                                        >
                                            View on Packagist
                                        </x-filament::button>

                                        <div
                                            style="
                                                padding: 0.85rem 1rem;
                                                border-radius: 0.9rem;
                                                background: rgb(28, 25, 23);
                                                color: white;
                                            "
                                        >
                                            <code style="display: block; word-break: break-word; font-size: 0.88rem; line-height: 1.7;">{{ $packageRequireCommand }}</code>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <x-filament::section
                    heading="Install the probe package in the remote app"
                    description="Run these commands inside the Laravel application you want to monitor. The package publishes its config, seeds the required environment keys, and exposes the shared health surface this monitor understands."
                    :icon="Heroicon::OutlinedCommandLine"
                >
                    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr));">
                        <div
                            style="
                                display: grid;
                                gap: 0.75rem;
                                padding: 1rem;
                                border-radius: 1rem;
                                background: rgb(28, 25, 23);
                                color: white;
                            "
                        >
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedArrowDownTray">
                                    Remote app install
                                </x-filament::badge>

                                <x-filament::button
                                    :href="$packageRepositoryUrl"
                                    tag="a"
                                    target="_blank"
                                    color="gray"
                                    :icon="Heroicon::OutlinedArrowTopRightOnSquare"
                                >
                                    Package repo
                                </x-filament::button>
                            </div>

                            <pre style="margin: 0; white-space: pre-wrap; font-size: 0.94rem; line-height: 1.8;"><code>{{ $installCommand }}</code></pre>
                        </div>

                        <div style="display: grid; gap: 1rem;">
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                "
                            >
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <x-filament::badge color="success" :icon="Heroicon::OutlinedCheckCircle">
                                        Package outcome
                                    </x-filament::badge>
                                </div>

                                <ul style="margin: 0; padding-left: 1rem; display: grid; gap: 0.55rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                    @foreach ($installOutcomes as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                "
                            >
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <x-filament::badge color="gray" :icon="Heroicon::OutlinedAdjustmentsHorizontal">
                                        Values that matter most
                                    </x-filament::badge>
                                </div>

                                <ul style="margin: 0; padding-left: 1rem; display: grid; gap: 0.55rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                    @foreach ($criticalRemoteValues as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                <div style="display: grid; gap: 1.25rem; grid-template-columns: minmax(0, 1fr);">
                    <x-filament::section
                        heading="Pull sync from this monitor"
                        description="Best for operator-led setup when this monitor can reach the remote app directly."
                        :icon="Heroicon::OutlinedArrowDownTray"
                    >
                        <div style="display: grid; gap: 1rem;">
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedDocumentMagnifyingGlass">
                                    Verify metadata
                                </x-filament::badge>
                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.75;"><code>{{ $pullMetadataCurl }}</code></pre>
                            </div>

                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedHeart">
                                    Verify health
                                </x-filament::badge>
                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.75;"><code>{{ $pullHealthCurl }}</code></pre>
                            </div>

                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: rgba(250, 250, 249, 1);
                                "
                            >
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                                    <x-filament::badge color="warning" :icon="Heroicon::OutlinedGlobeAlt">
                                        Monitor steps
                                    </x-filament::badge>

                                    <x-filament::button
                                        :href="$integrationCreateUrl"
                                        tag="a"
                                        color="warning"
                                        :icon="Heroicon::OutlinedPlus"
                                    >
                                        New integration
                                    </x-filament::button>
                                </div>

                                <ol style="margin: 0; padding-left: 1rem; display: grid; gap: 0.55rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                    @foreach ($pullSteps as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section
                        heading="Push registration from the remote app"
                        description="Best for first-time onboarding when the remote app should self-register against this monitor."
                        :icon="Heroicon::OutlinedArrowUpOnSquare"
                    >
                        <div style="display: grid; gap: 1rem;">
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedCog8Tooth">
                                    Remote environment
                                </x-filament::badge>
                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.75;"><code>{{ $pushEnvSnippet }}</code></pre>
                            </div>

                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedCommandLine">
                                    Registration command
                                </x-filament::badge>
                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.75;"><code>{{ $pushRegisterCommand }}</code></pre>
                            </div>

                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: rgba(250, 250, 249, 1);
                                "
                            >
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <x-filament::badge color="warning" :icon="Heroicon::OutlinedShieldCheck">
                                        Private endpoint
                                    </x-filament::badge>

                                    <x-filament::badge :color="$hasPushToken ? 'success' : 'danger'" :icon="$hasPushToken ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle">
                                        {{ $hasPushToken ? 'Token configured' : 'Token missing' }}
                                    </x-filament::badge>
                                </div>

                                <div style="display: grid; gap: 0.35rem;">
                                    <strong>Remote package target</strong>
                                    <code style="word-break: break-all; font-size: 0.9rem;">{{ $registrationEndpoint }}</code>
                                </div>

                                <span style="font-size: 0.92rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                    If the remote command returns <code>401</code>, the monitor token in settings and the remote
                                    app environment do not match yet.
                                </span>
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                <x-filament::section
                    heading="What the monitor generates automatically"
                    description="Once the metadata payload is accepted, the monitor keeps package-owned connection details in sync while preserving local incident history and presentation choices."
                    :icon="Heroicon::OutlinedBolt"
                >
                    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));">
                        @foreach ($generatedArtifacts as $artifact)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.6rem;
                                    padding: 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: rgba(250, 250, 249, 1);
                                "
                            >
                                <x-filament::badge color="success" :icon="Heroicon::OutlinedCheckBadge">
                                    Generated
                                </x-filament::badge>

                                <span style="font-size: 0.94rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                    {{ $artifact }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <div
                style="
                    display: grid;
                    gap: 1.25rem;
                    align-content: start;
                    grid-template-columns: repeat(auto-fit, minmax(21rem, 1fr));
                "
            >
                <x-filament::section
                    heading="Monitor details"
                    description="Use these values when another Laravel app needs to know where this monitor lives and what it expects."
                    :icon="Heroicon::OutlinedServerStack"
                >
                    <div style="display: grid; gap: 0.85rem;">
                        <div
                            style="
                                display: grid;
                                gap: 0.35rem;
                                padding: 0.9rem 1rem;
                                border-radius: 1rem;
                                border: 1px solid rgba(231, 229, 228, 1);
                            "
                        >
                            <x-filament::badge color="gray" :icon="Heroicon::OutlinedGlobeAlt">
                                Monitor URL
                            </x-filament::badge>
                            <code style="word-break: break-all; font-size: 0.9rem;">{{ $monitorUrl }}</code>
                        </div>

                        <div
                            style="
                                display: grid;
                                gap: 0.35rem;
                                padding: 0.9rem 1rem;
                                border-radius: 1rem;
                                border: 1px solid rgba(231, 229, 228, 1);
                            "
                        >
                            <x-filament::badge color="gray" :icon="Heroicon::OutlinedCube">
                                Package name
                            </x-filament::badge>
                            <code style="word-break: break-all; font-size: 0.9rem;">{{ $packageName }}</code>
                        </div>

                        <div
                            style="
                                display: grid;
                                gap: 0.35rem;
                                padding: 0.9rem 1rem;
                                border-radius: 1rem;
                                border: 1px solid rgba(231, 229, 228, 1);
                            "
                        >
                            <x-filament::badge :color="$hasPushToken ? 'success' : 'danger'" :icon="$hasPushToken ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle">
                                Push token status
                            </x-filament::badge>
                            <span style="font-size: 0.92rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                {{ $hasPushToken ? 'Configured and ready for push registration.' : 'Missing. Configure it in monitor settings first.' }}
                            </span>
                        </div>

                        @if ($hasPushToken)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.35rem;
                                    padding: 0.9rem 1rem;
                                    border-radius: 1rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedKey">
                                    Current push token
                                </x-filament::badge>
                                <code style="word-break: break-all; font-size: 0.84rem; line-height: 1.7;">{{ $pushToken }}</code>
                            </div>
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section
                    heading="Troubleshooting shortcuts"
                    description="Use these checks when the package is installed but the monitor does not behave the way operators expect."
                    :icon="Heroicon::OutlinedWrenchScrewdriver"
                >
                    <div style="display: grid; gap: 0.85rem;">
                        @foreach ($troubleshooting as $item)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.45rem;
                                    padding: 0.95rem 1rem;
                                    border-radius: 1rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedQuestionMarkCircle">
                                    Check this
                                </x-filament::badge>

                                <span style="font-size: 0.92rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                    {{ $item }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <x-filament::section
                    heading="Recommended operating pattern"
                    description="Start with pull sync to validate reachability and credentials. Add push registration after that if the remote team should be able to re-register or refresh metadata without entering this panel."
                    :icon="Heroicon::OutlinedLightBulb"
                    style="grid-column: 1 / -1;"
                >
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                        <x-filament::badge color="warning" :icon="Heroicon::OutlinedArrowDownTray">
                            Start with pull
                        </x-filament::badge>

                        <x-filament::badge color="success" :icon="Heroicon::OutlinedArrowUpOnSquare">
                            Add push after validation
                        </x-filament::badge>

                        <x-filament::badge color="gray" :icon="Heroicon::OutlinedArrowsRightLeft">
                            Hybrid works best for most teams
                        </x-filament::badge>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
