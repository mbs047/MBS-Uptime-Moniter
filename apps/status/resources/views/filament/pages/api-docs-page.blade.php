@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-panels::page>
    <div style="display: grid; gap: 1.25rem;">
        <x-filament::section
            heading="API surface overview"
            description="These routes power the public status page, third-party embeds, and package-driven remote integration onboarding. Each card includes a live preview button so operators can inspect the current response without leaving admin."
            :icon="Heroicon::OutlinedCodeBracketSquare"
        >
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <x-filament::badge color="success" :icon="Heroicon::OutlinedGlobeAlt">
                    Public status API
                </x-filament::badge>

                <x-filament::badge color="warning" :icon="Heroicon::OutlinedShieldCheck">
                    Private integration API
                </x-filament::badge>

                <x-filament::badge color="gray" :icon="Heroicon::OutlinedSparkles">
                    Live preview built in
                </x-filament::badge>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Public status API"
            description="Unauthenticated JSON endpoints for your public status page, embeds, or downstream consumers."
            :icon="Heroicon::OutlinedGlobeAlt"
        >
            <div style="display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr);">
                @foreach ($endpointCatalog['public'] as $endpoint)
                    @php
                        $response = $endpointResponses[$endpoint['key']] ?? null;
                    @endphp

                    <div
                        style="
                            display: grid;
                            gap: 1rem;
                            padding: 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(231, 229, 228, 1);
                            background: rgba(250, 250, 249, 1);
                        "
                    >
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; flex-wrap: wrap;">
                            <div style="display: grid; gap: 0.5rem;">
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <x-filament::badge :color="$endpoint['method'] === 'GET' ? 'success' : 'warning'" :icon="$endpoint['method'] === 'GET' ? Heroicon::OutlinedArrowDownTray : Heroicon::OutlinedArrowUpOnSquare">
                                        {{ $endpoint['method'] }}
                                    </x-filament::badge>

                                    <x-filament::badge color="gray" :icon="Heroicon::OutlinedLockOpen">
                                        {{ $endpoint['auth'] }}
                                    </x-filament::badge>
                                </div>

                                <div style="display: grid; gap: 0.35rem;">
                                    <strong style="font-size: 1rem;">{{ $endpoint['title'] }}</strong>
                                    <code style="font-size: 0.9rem; word-break: break-all;">{{ $endpoint['path'] }}</code>
                                </div>
                            </div>

                            <x-filament::button
                                color="warning"
                                :icon="Heroicon::OutlinedPlay"
                                wire:click="testEndpoint('{{ $endpoint['key'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="testEndpoint"
                            >
                                Test request
                            </x-filament::button>
                        </div>

                        <span style="font-size: 0.93rem; line-height: 1.7; color: rgb(87, 83, 78);">
                            {{ $endpoint['description'] }}
                        </span>

                        @if ($endpoint['key'] === 'subscribers')
                            <div style="display: grid; gap: 0.5rem;">
                                <label style="font-size: 0.85rem; font-weight: 600; color: rgb(68, 64, 60);">Preview email payload</label>
                                <input
                                    type="email"
                                    wire:model.live="subscriberPreviewEmail"
                                    style="
                                        width: 100%;
                                        padding: 0.8rem 0.9rem;
                                        border-radius: 0.9rem;
                                        border: 1px solid rgba(214, 211, 209, 1);
                                        background: white;
                                        font-size: 0.95rem;
                                    "
                                />
                            </div>
                        @endif

                        @if ($endpoint['requestBody'])
                            <div
                                style="
                                    display: grid;
                                    gap: 0.6rem;
                                    padding: 0.9rem 1rem;
                                    border-radius: 0.95rem;
                                    background: rgb(28, 25, 23);
                                    color: white;
                                "
                            >
                                <x-filament::badge color="gray" :icon="Heroicon::OutlinedCommandLine">
                                    Example request body
                                </x-filament::badge>
                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.85rem; line-height: 1.7;"><code>{{ json_encode($endpoint['requestBody'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                        @endif

                        <div style="display: grid; gap: 0.45rem;">
                            <span style="font-size: 0.83rem; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: rgb(120, 113, 108);">
                                Response highlights
                            </span>

                            <ul style="margin: 0; padding-left: 1rem; display: grid; gap: 0.45rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                @foreach ($endpoint['highlights'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($response)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 0.95rem 1rem;
                                    border-radius: 0.95rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: white;
                                "
                            >
                                <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        <x-filament::badge :color="$response['ok'] ? 'success' : 'danger'" :icon="$response['ok'] ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle">
                                            HTTP {{ $response['status'] }}
                                        </x-filament::badge>

                                        <x-filament::badge color="gray" :icon="Heroicon::OutlinedClock">
                                            {{ $response['tested_at'] }}
                                        </x-filament::badge>
                                    </div>

                                    <span style="font-size: 0.8rem; color: rgb(120, 113, 108);">
                                        {{ $response['content_type'] ?? 'application/json' }}
                                    </span>
                                </div>

                                <span style="font-size: 0.88rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                    {{ $response['preview_note'] }}
                                </span>

                                @if (! empty($response['exception']))
                                    <div style="font-size: 0.85rem; color: rgb(180, 83, 9);">
                                        {{ $response['exception'] }}
                                    </div>
                                @endif

                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.84rem; line-height: 1.7; color: rgb(28, 25, 23);"><code>{{ $response['body'] }}</code></pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Private integration API"
            description="Authenticated routes used by the Laravel probe package when remote apps self-register into this monitor."
            :icon="Heroicon::OutlinedShieldCheck"
        >
            <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(21rem, 1fr));">
                @foreach ($endpointCatalog['private'] as $endpoint)
                    @php
                        $response = $endpointResponses[$endpoint['key']] ?? null;
                    @endphp

                    <div
                        style="
                            display: grid;
                            gap: 1rem;
                            padding: 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(231, 229, 228, 1);
                            background: rgba(250, 250, 249, 1);
                        "
                    >
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; flex-wrap: wrap;">
                            <div style="display: grid; gap: 0.5rem;">
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <x-filament::badge color="warning" :icon="Heroicon::OutlinedArrowUpOnSquare">
                                        {{ $endpoint['method'] }}
                                    </x-filament::badge>

                                    <x-filament::badge color="danger" :icon="Heroicon::OutlinedKey">
                                        {{ $endpoint['auth'] }}
                                    </x-filament::badge>
                                </div>

                                <div style="display: grid; gap: 0.35rem;">
                                    <strong style="font-size: 1rem;">{{ $endpoint['title'] }}</strong>
                                    <code style="font-size: 0.9rem; word-break: break-all;">{{ $endpoint['path'] }}</code>
                                </div>
                            </div>

                            <x-filament::button
                                :color="$hasProbeRegistrationToken ? 'warning' : 'gray'"
                                :icon="Heroicon::OutlinedPlay"
                                wire:click="testEndpoint('{{ $endpoint['key'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="testEndpoint"
                            >
                                Test request
                            </x-filament::button>
                        </div>

                        <span style="font-size: 0.93rem; line-height: 1.7; color: rgb(87, 83, 78);">
                            {{ $endpoint['description'] }}
                        </span>

                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <x-filament::badge :color="$hasProbeRegistrationToken ? 'success' : 'danger'" :icon="$hasProbeRegistrationToken ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle">
                                {{ $hasProbeRegistrationToken ? 'Probe registration token configured' : 'Probe registration token missing' }}
                            </x-filament::badge>
                        </div>

                        <div
                            style="
                                display: grid;
                                gap: 0.6rem;
                                padding: 0.9rem 1rem;
                                border-radius: 0.95rem;
                                background: rgb(28, 25, 23);
                                color: white;
                            "
                        >
                            <x-filament::badge color="gray" :icon="Heroicon::OutlinedCommandLine">
                                Example request body
                            </x-filament::badge>
                            <pre style="margin: 0; white-space: pre-wrap; font-size: 0.85rem; line-height: 1.7;"><code>{{ json_encode($endpoint['requestBody'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>

                        <div style="display: grid; gap: 0.45rem;">
                            <span style="font-size: 0.83rem; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: rgb(120, 113, 108);">
                                Contract highlights
                            </span>

                            <ul style="margin: 0; padding-left: 1rem; display: grid; gap: 0.45rem; line-height: 1.65; color: rgb(68, 64, 60);">
                                @foreach ($endpoint['highlights'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($response)
                            <div
                                style="
                                    display: grid;
                                    gap: 0.75rem;
                                    padding: 0.95rem 1rem;
                                    border-radius: 0.95rem;
                                    border: 1px solid rgba(231, 229, 228, 1);
                                    background: white;
                                "
                            >
                                <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        <x-filament::badge :color="$response['ok'] ? 'success' : 'danger'" :icon="$response['ok'] ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle">
                                            HTTP {{ $response['status'] }}
                                        </x-filament::badge>

                                        <x-filament::badge color="gray" :icon="Heroicon::OutlinedClock">
                                            {{ $response['tested_at'] }}
                                        </x-filament::badge>
                                    </div>

                                    <span style="font-size: 0.8rem; color: rgb(120, 113, 108);">
                                        {{ $response['content_type'] ?? 'application/json' }}
                                    </span>
                                </div>

                                <span style="font-size: 0.88rem; line-height: 1.65; color: rgb(87, 83, 78);">
                                    {{ $response['preview_note'] }}
                                </span>

                                @if (! empty($response['exception']))
                                    <div style="font-size: 0.85rem; color: rgb(180, 83, 9);">
                                        {{ $response['exception'] }}
                                    </div>
                                @endif

                                <pre style="margin: 0; white-space: pre-wrap; font-size: 0.84rem; line-height: 1.7; color: rgb(28, 25, 23);"><code>{{ $response['body'] }}</code></pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Related browser routes"
            description="These routes are not JSON APIs, but they complete the subscriber flow and are useful to operators when debugging email confirmation links."
            :icon="Heroicon::OutlinedEnvelope"
        >
            <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));">
                @foreach ($relatedSubscriberRoutes as $route)
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
                            <x-filament::badge color="gray" :icon="Heroicon::OutlinedArrowTopRightOnSquare">
                                {{ $route['method'] }}
                            </x-filament::badge>
                        </div>

                        <code style="font-size: 0.9rem; word-break: break-all;">{{ $route['path'] }}</code>

                        <span style="font-size: 0.92rem; line-height: 1.65; color: rgb(87, 83, 78);">
                            {{ $route['summary'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
