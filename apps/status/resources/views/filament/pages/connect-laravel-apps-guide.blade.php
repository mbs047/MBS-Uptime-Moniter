<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
        <div class="space-y-6">
            @unless ($hasPushToken)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold">The push registration token is not configured yet.</p>
                            <p class="text-sm leading-6 text-amber-900">
                                Create a monitor token first if you want remote Laravel apps to self-register with
                                <code class="rounded bg-amber-100 px-1.5 py-0.5 text-[0.8125rem] font-medium">php artisan status-probe:register</code>.
                                Pull-based sync can still work without it.
                            </p>
                        </div>

                        <a
                            href="{{ $settingsUrl }}"
                            class="inline-flex shrink-0 items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-950 transition hover:bg-amber-100"
                        >
                            Open monitor settings
                        </a>
                    </div>
                </section>
            @endunless

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Recommended flow</p>
                    <h2 class="text-2xl font-semibold tracking-tight text-stone-950">Install the probe package in the remote app, then let this monitor generate the rest.</h2>
                    <p class="max-w-3xl text-sm leading-6 text-stone-600">
                        The package exposes authenticated health and metadata endpoints. This monitor can then import the
                        service, components, and package-managed checks without manual JSON-path mapping or endpoint guesswork.
                    </p>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <p class="text-sm font-semibold text-stone-900">1. Install the package</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Run the install command in the Laravel app you want to monitor and keep its probe token private.</p>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <p class="text-sm font-semibold text-stone-900">2. Choose pull, push, or both</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Pull works from this admin panel. Push is faster for first-time onboarding when the remote app already knows this monitor URL.</p>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <p class="text-sm font-semibold text-stone-900">3. Review generated checks</p>
                        <p class="mt-2 text-sm leading-6 text-stone-600">After sync, verify the linked service, generated components, and shared-health checks before publishing incidents.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Remote app install</p>
                        <h2 class="text-xl font-semibold text-stone-950">Commands to run inside the other Laravel application</h2>
                        <p class="text-sm leading-6 text-stone-600">
                            These commands add the public package, publish its configuration, and seed the probe environment
                            keys the remote app needs.
                        </p>
                    </div>

                    <a
                        href="{{ $packageRepositoryUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-lg border border-stone-200 px-3 py-2 text-sm font-medium text-stone-700 transition hover:border-stone-300 hover:bg-stone-50"
                    >
                        View package repository
                    </a>
                </div>

                <pre class="mt-5 overflow-x-auto rounded-2xl bg-stone-950 px-4 py-4 text-sm leading-6 text-stone-100"><code>{{ $installCommand }}</code></pre>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">What the install command gives the remote app</p>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-stone-600">
                            <li>Authenticated <code class="rounded bg-stone-100 px-1.5 py-0.5 text-[0.8125rem]">/status/health</code> and <code class="rounded bg-stone-100 px-1.5 py-0.5 text-[0.8125rem]">/status/metadata</code> endpoints</li>
                            <li>Built-in contributors for app, database, and cache health</li>
                            <li>Optional queue and scheduler heartbeats for deeper operational coverage</li>
                        </ul>
                    </div>

                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">Remote app values that matter most</p>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-stone-600">
                            <li><code class="rounded bg-stone-100 px-1.5 py-0.5 text-[0.8125rem]">APP_URL</code> must be correct or the monitor will import broken URLs</li>
                            <li><code class="rounded bg-stone-100 px-1.5 py-0.5 text-[0.8125rem]">STATUS_PROBE_TOKEN</code> protects the package endpoints</li>
                            <li>Custom health or metadata paths are supported and reflected in the metadata payload</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Pull model</p>
                    <h2 class="text-xl font-semibold text-stone-950">Use this when the monitor can reach the remote app directly</h2>
                    <p class="text-sm leading-6 text-stone-600">
                        This is the simplest admin-side workflow. Create a remote integration here with the app base URL and the
                        remote probe token, then let the monitor sync metadata and generate the linked service and checks.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">Verify metadata first</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-stone-950 px-4 py-4 text-sm leading-6 text-stone-100"><code>{{ $pullMetadataCurl }}</code></pre>
                    </div>

                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">Verify shared health payload</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-stone-950 px-4 py-4 text-sm leading-6 text-stone-100"><code>{{ $pullHealthCurl }}</code></pre>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <p class="text-sm font-semibold text-stone-900">Admin steps inside this monitor</p>
                    <ol class="mt-3 space-y-2 text-sm leading-6 text-stone-600">
                        <li>1. Open <a href="{{ $integrationCreateUrl }}" class="font-medium text-amber-700 hover:text-amber-800">Remote integrations</a> and create a new integration.</li>
                        <li>2. Enter the remote app base URL and the remote probe bearer token.</li>
                        <li>3. Save and let the monitor sync immediately, or use <span class="font-medium text-stone-900">Sync now</span> later from the record.</li>
                        <li>4. Review the generated service, components, and checks before relying on the public status page.</li>
                    </ol>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Push model</p>
                    <h2 class="text-xl font-semibold text-stone-950">Use this when the remote app should register itself with the monitor</h2>
                    <p class="text-sm leading-6 text-stone-600">
                        Push registration is ideal for first-time setup. Give the remote app this monitor URL and the monitor-side
                        registration token, then run the package registration command from the remote application.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">Remote app environment values</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-stone-950 px-4 py-4 text-sm leading-6 text-stone-100"><code>{{ $pushEnvSnippet }}</code></pre>
                    </div>

                    <div class="rounded-xl border border-stone-200 p-4">
                        <p class="text-sm font-semibold text-stone-900">Remote app registration command</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-stone-950 px-4 py-4 text-sm leading-6 text-stone-100"><code>{{ $pushRegisterCommand }}</code></pre>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <p class="text-sm font-semibold text-stone-900">What the remote app will call</p>
                    <p class="mt-2 text-sm leading-6 text-stone-600">
                        The package posts its metadata payload to
                        <code class="rounded bg-white px-1.5 py-0.5 text-[0.8125rem] font-medium">{{ $registrationEndpoint }}</code>.
                        If registration returns <code class="rounded bg-white px-1.5 py-0.5 text-[0.8125rem] font-medium">401</code>,
                        the monitor token in settings and the remote app environment do not match.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Generated records</p>
                    <h2 class="text-xl font-semibold text-stone-950">What this monitor creates for you automatically</h2>
                    <p class="text-sm leading-6 text-stone-600">
                        The goal is to remove repetitive manual setup. Once the probe metadata is accepted, the monitor keeps the
                        package-owned details in sync while preserving local incident history and presentation choices.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($generatedArtifacts as $artifact)
                        <div class="rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm leading-6 text-stone-700">
                            {{ $artifact }}
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Monitor details</p>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-stone-900">Monitor URL</dt>
                        <dd class="mt-1 break-all text-stone-600">{{ $monitorUrl }}</dd>
                    </div>

                    <div>
                        <dt class="font-medium text-stone-900">Package name</dt>
                        <dd class="mt-1 text-stone-600">{{ $packageName }}</dd>
                    </div>

                    <div>
                        <dt class="font-medium text-stone-900">Push token status</dt>
                        <dd class="mt-1 text-stone-600">{{ $hasPushToken ? 'Configured and ready for push registration.' : 'Missing. Configure it in monitor settings first.' }}</dd>
                    </div>

                    @if ($hasPushToken)
                        <div>
                            <dt class="font-medium text-stone-900">Current push token</dt>
                            <dd class="mt-2 overflow-x-auto rounded-xl bg-stone-950 px-3 py-3 text-xs leading-6 text-stone-100">{{ $pushToken }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">When users get stuck</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-stone-600">
                    <li>If sync succeeds but checks stay unhealthy, test the remote health endpoint separately from the metadata endpoint.</li>
                    <li>If the remote app changed <code class="rounded bg-stone-100 px-1.5 py-0.5 text-[0.8125rem]">STATUS_PROBE_HEALTH_PATH</code> or metadata path, re-sync so the monitor picks up the new URLs.</li>
                    <li>If queue or scheduler health looks stale, confirm the remote app is running its queue worker and scheduler heartbeat command on a shared cache store.</li>
                </ul>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Best practice</p>
                <p class="mt-4 text-sm leading-6 text-stone-600">
                    Start with pull sync so you can validate the remote token and endpoints directly. Once that works, add push
                    registration if the remote team should be able to re-register or refresh metadata without entering this admin panel.
                </p>
            </section>
        </aside>
    </div>
</x-filament-panels::page>
