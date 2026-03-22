<x-status-layout :title="$settings->seo_title ?: $settings->brand_name" :description="$settings->seo_description ?: $settings->brand_tagline">
    <div class="status-page">
        @if (session('status'))
            <div class="flash-banner">{{ session('status') }}</div>
        @endif

        <header class="status-topbar">
            <div class="brand-mark">
                <span class="brand-kicker">Production status</span>
                <strong class="brand-name">{{ $settings->brand_name }}</strong>
                @if ($settings->brand_tagline)
                    <span class="status-subtle">{{ $settings->brand_tagline }}</span>
                @endif
            </div>

            <div class="status-subtle">
                Updated {{ \Illuminate\Support\Carbon::parse($summary['generated_at'])->diffForHumans() }}
            </div>
        </header>

        <section class="status-grid">
            <article class="status-card status-card--hero">
                <div class="status-hero-grid">
                    <div>
                        <span class="brand-kicker">Current state</span>
                        <h1 class="status-display">
                            {{ match ($summary['overall_status']) {
                                'operational' => 'Systems are running normally.',
                                'degraded' => 'Some operations are slower than normal.',
                                'partial_outage' => 'A subset of requests is impaired.',
                                'major_outage' => 'A major service interruption is in progress.',
                                'maintenance' => 'Scheduled maintenance is underway.',
                                default => 'Status is being updated.',
                            } }}
                        </h1>
                        <p class="status-copy">
                            This page reports the live operational state of the production platform, with incident notes,
                            service-by-service health, and ninety days of recent availability history.
                        </p>
                    </div>

                    <div class="kpi-stack">
                        <div>
                            <x-status-badge :status="$summary['overall_status']" />
                        </div>
                        <div class="kpi-item">
                            <span class="kpi-label">Active incidents</span>
                            <span class="kpi-value">{{ $summary['active_incident_count'] }}</span>
                        </div>
                        <div class="kpi-item">
                            <span class="kpi-label">Tracked components</span>
                            <span class="kpi-value">{{ $summary['affected_component_count'] }}</span>
                        </div>
                    </div>
                </div>
            </article>

            <aside class="status-card status-card--aside">
                <span class="brand-kicker">Email updates</span>
                <h2 class="section-title">Subscribe to incident notices</h2>
                <p class="section-lede">
                    Receive emails only when a published incident is created, updated, or resolved.
                </p>

                <form class="form-stack" data-subscriber-form>
                    <div>
                        <label class="field-label" for="status-email">Email address</label>
                        <input id="status-email" name="email" type="email" class="field-input" placeholder="team@example.com" required>
                    </div>

                    <button type="submit" class="button-primary">Subscribe</button>
                    <p class="status-subtle" data-subscriber-feedback>We'll ask you to confirm before anything is sent.</p>
                </form>
            </aside>
        </section>

        <section class="status-card status-card--section" style="margin-top: 1.35rem;">
            <span class="brand-kicker">Live incidents</span>
            <h2 class="section-title">Incident timeline</h2>
            <p class="section-lede">
                Published incidents and scheduled maintenance appear here before they reach your inbox.
            </p>

            <div class="incident-list">
                @forelse ($activeIncidents as $incident)
                    <article class="incident-card">
                        <div class="incident-meta">
                            <x-status-badge :status="$incident['severity']" />
                            <span>{{ $incident['started_at'] ? \Illuminate\Support\Carbon::parse($incident['started_at'])->diffForHumans() : 'Published' }}</span>
                        </div>
                        <h3 class="incident-title">
                            <a href="{{ route('status.incidents.show', $incident['slug']) }}">{{ $incident['title'] }}</a>
                        </h3>
                        @if ($incident['summary'])
                            <p class="section-lede">{{ $incident['summary'] }}</p>
                        @endif
                    </article>
                @empty
                    <article class="incident-card">
                        <div class="incident-meta">
                            <x-status-badge status="operational" />
                            <span>No active incidents or maintenance</span>
                        </div>
                        <p class="section-lede">The monitored production estate is currently operating without any published incident notices.</p>
                    </article>
                @endforelse
            </div>
        </section>

        <section class="status-card status-card--section" style="margin-top: 1.35rem;">
            <span class="brand-kicker">Services</span>
            <h2 class="section-title">Service health and 90-day availability</h2>
            <p class="section-lede">
                Component status reflects the highest-severity result between automated health checks and any active published incident affecting that service.
            </p>

            <div class="service-list">
                @foreach ($services as $service)
                    <article class="service-card">
                        <div class="service-header">
                            <div>
                                <div class="service-meta">
                                    <x-status-badge :status="$service['status']" />
                                    <span>{{ $service['name'] }}</span>
                                </div>
                                @if ($service['description'])
                                    <p class="section-lede">{{ $service['description'] }}</p>
                                @endif
                            </div>
                        </div>

                        @foreach ($service['components'] as $serviceComponent)
                            <div class="component-row">
                                <div>
                                    <div class="inline-cluster">
                                        <h3 class="component-name">{{ $serviceComponent['display_name'] }}</h3>
                                        <x-status-badge :status="$serviceComponent['status']" />
                                    </div>
                                    @if ($serviceComponent['description'])
                                        <p class="component-description">{{ $serviceComponent['description'] }}</p>
                                    @endif

                                    @if (! empty($serviceComponent['active_incidents']))
                                        <div class="inline-cluster" style="margin-top: 0.7rem;">
                                            @foreach ($serviceComponent['active_incidents'] as $reference)
                                                <a href="{{ route('status.incidents.show', $reference['slug']) }}" class="button-secondary" style="padding: 0.45rem 0.8rem;">
                                                    {{ $reference['title'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="uptime-box">
                                    <div>
                                        <div class="kpi-label">90-day uptime</div>
                                        <div class="uptime-percent">{{ number_format((float) $serviceComponent['uptime_90d_percent'], 2) }}%</div>
                                    </div>
                                    <div class="uptime-bars" aria-label="90 day uptime bars">
                                        @foreach ($serviceComponent['uptime_bars'] as $bar)
                                            <span class="uptime-bar uptime-bar--{{ $bar['state'] }}" title="{{ $bar['day'] }}{{ $bar['percentage'] !== null ? ': '.number_format($bar['percentage'], 2).'%' : ': no data' }}"></span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </article>
                @endforeach
            </div>
        </section>

        <section class="status-card status-card--section" style="margin-top: 1.35rem;">
            <span class="brand-kicker">Recent history</span>
            <h2 class="section-title">Published incident archive</h2>
            <div class="history-list">
                @foreach ($incidentHistory as $incident)
                    <article class="history-item">
                        <div class="incident-meta">
                            <x-status-badge :status="$incident['severity']" />
                            <span>{{ $incident['published_at'] ? \Illuminate\Support\Carbon::parse($incident['published_at'])->format('M j, Y g:i A T') : 'Published incident' }}</span>
                        </div>
                        <h3 class="history-title">
                            <a href="{{ route('status.incidents.show', $incident['slug']) }}">{{ $incident['title'] }}</a>
                        </h3>
                        @if ($incident['latest_update'])
                            <p class="section-lede">{{ \Illuminate\Support\Str::limit($incident['latest_update'], 220) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
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
