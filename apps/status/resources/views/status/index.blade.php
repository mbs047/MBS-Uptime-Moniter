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
        $showServiceName = count($services) > 1;
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
                <div class="status-nav__controls">
                    <button type="button" class="status-nav__link status-nav__button" data-open-subscribe-modal>
                        <span class="status-nav__button-label">Subscribe to updates</span>
                    </button>
                    <span class="status-nav__stamp" data-relative-time="{{ $summary['generated_at'] }}" data-relative-prefix="Updated">
                        Updated {{ \Illuminate\Support\Carbon::parse($summary['generated_at'])->diffForHumans() }}
                    </span>
                </div>
            </nav>
        </header>

        <section class="status-overview">
            <div class="status-overview__main">
                <div class="status-overview__layout">
                    <div class="status-overview__copy">
                        <span class="status-eyebrow">Current status</span>
                        <h1 class="status-headline">{{ $overallHeadline }}</h1>
                        <p class="status-copy">{{ $overallCopy }}</p>

                        <div class="status-overview__note">
                            <span class="status-overview__note-line"></span>
                            <p>Live production visibility across public services, component health checks, and published incidents.</p>
                        </div>
                    </div>

                    <aside class="status-overview__panel" aria-label="Current status snapshot">
                        <div class="status-overview__panel-header">
                            <div>
                                <p class="panel-copy">A compact operational view of the public estate right now.</p>
                            </div>
                            <x-status-badge :status="$summary['overall_status']" />
                        </div>

                        <div class="status-overview__stats">
                            <div class="status-overview__stat">
                                <span>Service groups</span>
                                <strong>{{ count($services) }}</strong>
                                <small>public production groups</small>
                            </div>

                            <div class="status-overview__stat">
                                <span>Components</span>
                                <strong>{{ $summary['affected_component_count'] }}</strong>
                                <small>public monitored components</small>
                            </div>

                            <div class="status-overview__stat">
                                <span>Active incidents</span>
                                <strong>{{ $summary['active_incident_count'] }}</strong>
                                <small>{{ $summary['active_incident_count'] === 0 ? 'no active notices' : 'published right now' }}</small>
                            </div>

                            <div class="status-overview__stat">
                                <span>Last updated</span>
                                <strong data-relative-time="{{ $summary['generated_at'] }}">
                                    {{ \Illuminate\Support\Carbon::parse($summary['generated_at'])->diffForHumans() }}
                                </strong>
                                <small>status snapshot generated</small>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        @if ($activeIncidents !== [])
            <section class="status-section">
                <div class="status-section__header">
                    <div>
                        <span class="status-eyebrow">Current notices</span>
                        <h2 class="section-title">Active incidents and maintenance</h2>
                        <p class="section-lede">Any published incident appears here before it reaches subscriber inboxes.</p>
                    </div>
                </div>

                <div class="notice-list">
                    @foreach ($activeIncidents as $incident)
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
                    @endforeach
                </div>
            </section>
        @endif

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
                    <article class="service-panel">
                        <div class="service-panel__header">
                            <div>
                                @if ($showServiceName)
                                    <div class="service-panel__title">
                                        <h3>{{ $service['name'] }}</h3>
                                        @if ($service['status'] !== 'operational')
                                            <x-status-badge :status="$service['status']" />
                                        @endif
                                    </div>
                                @else
                                    <div class="service-panel__title service-panel__title--single">
                                        <span class="service-panel__context">Public components</span>
                                        @if ($service['status'] !== 'operational')
                                            <x-status-badge :status="$service['status']" />
                                        @endif
                                    </div>
                                @endif

                                @if ($service['description'])
                                    <p class="section-lede">{{ $service['description'] }}</p>
                                @endif
                            </div>

                            <div class="service-panel__summary">
                                <span>{{ $service['component_count'] }} component{{ $service['component_count'] === 1 ? '' : 's' }}</span>
                                <strong>{{ number_format((float) $service['uptime_90d_percent'], 2) }}% uptime</strong>
                            </div>
                        </div>

                        <div class="component-list">
                            @foreach ($service['components'] as $serviceComponent)
                                <article class="component-item">
                                    <div class="component-item__top">
                                        <div class="component-item__copy">
                                            <div class="component-item__heading">
                                                <h4 class="component-name">{{ $serviceComponent['display_name'] }}</h4>
                                                @if ($serviceComponent['status'] !== 'operational')
                                                    <x-status-badge :status="$serviceComponent['status']" />
                                                @endif
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

                                        <div class="component-item__summary">
                                            <span>90-day uptime</span>
                                            <strong>{{ number_format((float) $serviceComponent['uptime_90d_percent'], 2) }}%</strong>
                                        </div>
                                    </div>

                                    <div class="component-uptime">
                                        <div class="component-uptime__track" aria-label="{{ $serviceComponent['display_name'] }} 90 day uptime history">
                                            @foreach ($serviceComponent['uptime_bars'] as $bar)
                                                <div class="component-uptime__cell">
                                                    <span
                                                        class="component-uptime__bar component-uptime__bar--{{ $bar['state'] }}"
                                                        title="{{ $bar['date_label'] }}{{ $bar['percentage'] !== null ? ': '.number_format($bar['percentage'], 2).'%' : ': no data' }}"
                                                    ></span>

                                                    <div class="component-uptime__tooltip" role="tooltip">
                                                        <p class="component-uptime__tooltip-date">{{ $bar['date_label'] }}</p>
                                                        <p class="component-uptime__tooltip-stat">
                                                            @if ($bar['percentage'] !== null)
                                                                {{ number_format($bar['percentage'], 2) }}% uptime
                                                            @else
                                                                No uptime data recorded
                                                            @endif
                                                        </p>

                                                        <div class="component-uptime__tooltip-list">
                                                            @foreach ($bar['messages'] as $message)
                                                                <div class="component-uptime__tooltip-item">
                                                                    <span class="component-uptime__tooltip-dot component-uptime__tooltip-dot--{{ $message['severity'] }}"></span>
                                                                    <span>{{ $message['message'] }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
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

        <div class="status-footer-cta">
            <a href="{{ route('status.history') }}" class="button-secondary status-footer-cta__button">View history</a>
        </div>

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

    <div class="status-modal" data-subscribe-modal hidden>
        <div class="status-modal__backdrop" data-close-subscribe-modal></div>

        <div
            class="status-modal__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subscribe-modal-title"
            aria-describedby="subscribe-modal-copy"
        >
            <button type="button" class="status-modal__close" aria-label="Close subscribe dialog" data-close-subscribe-modal>&times;</button>

            <span class="status-eyebrow">Subscribe to updates</span>
            <h2 class="panel-title" id="subscribe-modal-title">Get incident emails</h2>
            <p class="panel-copy" id="subscribe-modal-copy">
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
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-subscribe-modal]');
            const openButtons = document.querySelectorAll('[data-open-subscribe-modal]');
            const closeButtons = document.querySelectorAll('[data-close-subscribe-modal]');
            const relativeTimeNodes = document.querySelectorAll('[data-relative-time]');
            const form = document.querySelector('[data-subscriber-form]');
            const feedback = document.querySelector('[data-subscriber-feedback]');
            const emailField = document.querySelector('#status-email');

            const formatRelativeTime = (value) => {
                const target = new Date(value);

                if (Number.isNaN(target.getTime())) {
                    return '';
                }

                const diffInSeconds = Math.round((target.getTime() - Date.now()) / 1000);
                const absoluteSeconds = Math.abs(diffInSeconds);

                if (absoluteSeconds < 45) {
                    return 'just now';
                }

                const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
                const intervals = [
                    ['year', 60 * 60 * 24 * 365],
                    ['month', 60 * 60 * 24 * 30],
                    ['week', 60 * 60 * 24 * 7],
                    ['day', 60 * 60 * 24],
                    ['hour', 60 * 60],
                    ['minute', 60],
                ];

                for (const [unit, seconds] of intervals) {
                    if (absoluteSeconds >= seconds) {
                        return formatter.format(Math.round(diffInSeconds / seconds), unit);
                    }
                }

                return formatter.format(diffInSeconds, 'second');
            };

            const refreshRelativeTimes = () => {
                relativeTimeNodes.forEach((node) => {
                    const relative = formatRelativeTime(node.dataset.relativeTime ?? '');
                    const prefix = node.dataset.relativePrefix ?? '';

                    node.textContent = prefix && relative ? `${prefix} ${relative}` : relative;
                });
            };

            const openModal = () => {
                if (!modal) return;

                modal.hidden = false;
                document.body.classList.add('status-modal-open');
                window.requestAnimationFrame(() => emailField?.focus());
            };

            const closeModal = () => {
                if (!modal) return;

                modal.hidden = true;
                document.body.classList.remove('status-modal-open');
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', openModal);
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && !modal.hidden) {
                    closeModal();
                }
            });

            refreshRelativeTimes();
            window.setInterval(refreshRelativeTimes, 60_000);

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
