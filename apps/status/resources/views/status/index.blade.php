<x-status-layout :title="$settings->seo_title ?: $settings->brand_name" :description="$settings->seo_description ?: $settings->brand_tagline">
    @php
        $overallHeadline = match ($summary['overall_status']) {
            'operational' => 'We’re fully operational',
            'degraded' => 'Some systems are experiencing degraded performance',
            'partial_outage' => 'Some systems are experiencing a partial outage',
            'major_outage' => 'We’re experiencing a major outage',
            'maintenance' => 'Scheduled maintenance is in progress',
            default => 'Status is being updated',
        };

        $overallCopy = match ($summary['overall_status']) {
            'operational' => 'All monitored production services are operating normally. If anything changes, this page and subscriber emails will update first.',
            'degraded' => 'A subset of requests or background jobs may be slower than expected while the team works through the issue.',
            'partial_outage' => 'Some functionality is impaired, but portions of the platform remain available.',
            'major_outage' => 'A major production interruption is affecting service availability. Follow the active incident timeline for updates.',
            'maintenance' => 'Maintenance is underway for one or more systems. Availability may change during the scheduled window.',
            default => 'Live production state is being refreshed.',
        };

        $windowStart = now()->subDays($uptimeWindowDays - 1);
        $windowLabel = $windowStart->format('M Y').' - '.now()->format('M Y');
    @endphp

    <div class="status-page">
        @if (session('status'))
            <div class="flash-banner">{{ session('status') }}</div>
        @endif

        <header class="status-header">
            <div class="status-brand">
                <span class="status-eyebrow">Production status</span>
                <a href="{{ route('status.index') }}" class="status-brand__name">{{ $settings->brand_name }}</a>
                @if ($settings->brand_tagline)
                    <p class="status-brand__tagline">{{ $settings->brand_tagline }}</p>
                @endif
            </div>

            <nav class="status-nav" aria-label="Status page">
                <a href="#subscribe" class="status-nav__link">Subscribe to updates</a>
                <a href="#history" class="status-nav__link">View history</a>
                <span class="status-nav__stamp">Updated {{ \Illuminate\Support\Carbon::parse($summary['generated_at'])->diffForHumans() }}</span>
            </nav>
        </header>

        <section class="status-overview">
            <div class="status-overview__main">
                <span class="status-eyebrow">Current status</span>
                <h1 class="status-headline">{{ $overallHeadline }}</h1>
                <p class="status-copy">{{ $overallCopy }}</p>

                <div class="status-meta-row">
                    <x-status-badge :status="$summary['overall_status']" />
                    <span>{{ count($services) }} service groups</span>
                    <span>{{ $summary['affected_component_count'] }} public components</span>
                    <span>{{ $summary['active_incident_count'] }} active incident{{ $summary['active_incident_count'] === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <aside class="status-overview__aside" id="subscribe">
                <span class="status-eyebrow">Subscribe to updates</span>
                <h2 class="panel-title">Get incident emails</h2>
                <p class="panel-copy">
                    Receive email only when a published incident is created, updated, or resolved.
                </p>

                <form class="form-stack" data-subscriber-form>
                    <div>
                        <label class="field-label" for="status-email">Email address</label>
                        <input id="status-email" name="email" type="email" class="field-input" placeholder="team@example.com" required>
                    </div>

                    <button type="submit" class="button-primary">Subscribe to updates</button>
                    <p class="status-subtle" data-subscriber-feedback>We’ll ask you to confirm before anything is sent.</p>
                </form>
            </aside>
        </section>

        <section class="status-section">
            <div class="status-section__header">
                <div>
                    <span class="status-eyebrow">Current notices</span>
                    <h2 class="section-title">Active incidents and maintenance</h2>
                    <p class="section-lede">Any published incident appears here before it reaches subscriber inboxes.</p>
                </div>
            </div>

            <div class="notice-list">
                @forelse ($activeIncidents as $incident)
                    <article class="notice-card">
                        <div class="notice-card__meta">
                            <x-status-badge :status="$incident['severity']" />
                            <span>{{ $incident['started_at'] ? \Illuminate\Support\Carbon::parse($incident['started_at'])->diffForHumans() : 'Published' }}</span>
                        </div>
                        <div class="notice-card__content">
                            <div>
                                <h3 class="notice-card__title">
                                    <a href="{{ route('status.incidents.show', $incident['slug']) }}">{{ $incident['title'] }}</a>
                                </h3>
                                @if ($incident['summary'])
                                    <p class="section-lede">{{ $incident['summary'] }}</p>
                                @endif
                            </div>
                            <a href="{{ route('status.incidents.show', $incident['slug']) }}" class="button-secondary">Open incident</a>
                        </div>
                    </article>
                @empty
                    <article class="notice-card notice-card--quiet">
                        <div class="notice-card__meta">
                            <x-status-badge status="operational" />
                            <span>No active incidents or maintenance</span>
                        </div>
                        <p class="section-lede">We’re not aware of any issues affecting the monitored production systems right now.</p>
                    </article>
                @endforelse
            </div>
        </section>

        <section class="status-section">
            <div class="status-section__header">
                <div>
                    <span class="status-eyebrow">System status</span>
                    <h2 class="section-title">Services</h2>
                    <p class="section-lede">
                        Each service rolls up automated health checks together with any active published incident affecting that service or its components.
                    </p>
                </div>

                <div class="status-section__meta">
                    <span>{{ $windowLabel }}</span>
                    <strong>{{ $uptimeWindowDays }}-day uptime window</strong>
                </div>
            </div>

            <div class="service-stack">
                @foreach ($services as $service)
                    @php
                        $serviceUptime = collect($service['components'])->avg('uptime_90d_percent') ?? 0;
                    @endphp

                    <article class="service-panel">
                        <div class="service-panel__header">
                            <div>
                                <div class="service-panel__title">
                                    <h3>{{ $service['name'] }}</h3>
                                    <x-status-badge :status="$service['status']" />
                                </div>

                                @if ($service['description'])
                                    <p class="section-lede">{{ $service['description'] }}</p>
                                @endif
                            </div>

                            <div class="service-panel__summary">
                                <span>{{ count($service['components']) }} component{{ count($service['components']) === 1 ? '' : 's' }}</span>
                                <strong>{{ number_format((float) $serviceUptime, 2) }}% uptime</strong>
                            </div>
                        </div>

                        <div class="component-list">
                            @foreach ($service['components'] as $serviceComponent)
                                <article class="component-item">
                                    <div class="component-item__copy">
                                        <div class="component-item__heading">
                                            <h4 class="component-name">{{ $serviceComponent['display_name'] }}</h4>
                                            <x-status-badge :status="$serviceComponent['status']" />
                                        </div>

                                        @if ($serviceComponent['description'])
                                            <p class="component-description">{{ $serviceComponent['description'] }}</p>
                                        @endif

                                        @if (! empty($serviceComponent['active_incidents']))
                                            <div class="inline-cluster">
                                                @foreach ($serviceComponent['active_incidents'] as $reference)
                                                    <a href="{{ route('status.incidents.show', $reference['slug']) }}" class="status-pill-link">
                                                        {{ $reference['title'] }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="component-item__health">
                                        <div class="uptime-summary">
                                            <span class="uptime-summary__label">90-day uptime</span>
                                            <strong>{{ number_format((float) $serviceComponent['uptime_90d_percent'], 2) }}%</strong>
                                        </div>
                                        <div class="uptime-bars" aria-label="90 day uptime bars">
                                            @foreach ($serviceComponent['uptime_bars'] as $bar)
                                                <span class="uptime-bar uptime-bar--{{ $bar['state'] }}" title="{{ $bar['day'] }}{{ $bar['percentage'] !== null ? ': '.number_format($bar['percentage'], 2).'%' : ': no data' }}"></span>
                                            @endforeach
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="status-section" id="history">
            <div class="status-section__header">
                <div>
                    <span class="status-eyebrow">History</span>
                    <h2 class="section-title">Recent incident history</h2>
                    <p class="section-lede">A rolling archive of the latest published notices and maintenance updates.</p>
                </div>
            </div>

            <div class="history-feed">
                @foreach ($incidentHistory as $incident)
                    <article class="history-entry">
                        <div class="history-entry__meta">
                            <x-status-badge :status="$incident['severity']" />
                            <span>{{ $incident['published_at'] ? \Illuminate\Support\Carbon::parse($incident['published_at'])->format('M j, Y g:i A T') : 'Published incident' }}</span>
                        </div>
                        <div class="history-entry__content">
                            <div>
                                <h3 class="history-title">
                                    <a href="{{ route('status.incidents.show', $incident['slug']) }}">{{ $incident['title'] }}</a>
                                </h3>
                                @if ($incident['latest_update'])
                                    <p class="section-lede">{{ \Illuminate\Support\Str::limit($incident['latest_update'], 220) }}</p>
                                @elseif ($incident['summary'])
                                    <p class="section-lede">{{ \Illuminate\Support\Str::limit($incident['summary'], 220) }}</p>
                                @endif
                            </div>
                            <a href="{{ route('status.incidents.show', $incident['slug']) }}" class="history-entry__link">View details</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <footer class="status-footer">
            <p>
                Availability metrics are reported at an aggregate level across all public production services, checks, and incident states.
                Individual customer experience may vary depending on the features they use.
            </p>

            @if ($settings->support_email)
                <a href="mailto:{{ $settings->support_email }}" class="status-footer__link">{{ $settings->support_email }}</a>
            @endif
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-subscriber-form]');
            const feedback = document.querySelector('[data-subscriber-feedback]');

            if (!form || !feedback) return;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const payload = Object.fromEntries(new FormData(form).entries());
                feedback.textContent = 'Sending confirmation email...';

                try {
                    const response = await fetch('/api/status/subscribers', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    feedback.textContent = data.message ?? 'Confirmation email sent.';
                    form.reset();
                } catch (error) {
                    feedback.textContent = 'Something went wrong while subscribing. Please try again.';
                }
            });
        });
    </script>
</x-status-layout>
