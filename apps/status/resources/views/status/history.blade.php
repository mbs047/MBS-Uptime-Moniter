<x-status-layout :title="($settings->seo_title ?: $settings->brand_name).' · History'" :description="'Published incident archive and maintenance history for '.$settings->brand_name">
    <div class="status-page status-page--detail">
        <header class="status-header">
            <div class="status-brand">
                <span class="status-eyebrow">History</span>
                <a href="{{ route('status.index') }}" class="status-brand__name">Return to status overview</a>
                <p class="status-brand__tagline">Published incidents, maintenance notices, and recent operational updates.</p>
            </div>

            <nav class="status-nav" aria-label="History page">
                <a href="{{ route('status.index') }}" class="status-nav__link">Current status</a>
                <span class="status-nav__stamp">{{ count($incidentHistory) }} published notice{{ count($incidentHistory) === 1 ? '' : 's' }}</span>
            </nav>
        </header>

        <section class="detail-hero">
            <div class="detail-hero__main">
                <span class="status-eyebrow">Published archive</span>
                <h1 class="detail-title">Incident history</h1>
                <p class="status-copy">
                    Review every published incident and maintenance notice in one place, with active incidents pinned first and resolved history following behind.
                </p>

                <div class="status-meta-row">
                    <span>{{ $historyStats['published_incidents'] }} published notice{{ $historyStats['published_incidents'] === 1 ? '' : 's' }}</span>
                    <span>{{ $historyStats['active_incidents'] }} active</span>
                    <span>{{ $historyStats['resolved_incidents'] }} resolved</span>
                    <span>{{ $historyStats['maintenance_incidents'] }} maintenance</span>
                </div>
            </div>

            <aside class="detail-hero__aside">
                <div class="detail-stat">
                    <span>Published</span>
                    <strong>{{ $historyStats['published_incidents'] }}</strong>
                </div>
                <div class="detail-stat">
                    <span>Active</span>
                    <strong>{{ $historyStats['active_incidents'] }}</strong>
                </div>
                <div class="detail-stat">
                    <span>Resolved</span>
                    <strong>{{ $historyStats['resolved_incidents'] }}</strong>
                </div>
            </aside>
        </section>

        @if ($activeIncidents !== [])
            <section class="status-section">
                <div class="status-section__header">
                    <div>
                        <span class="status-eyebrow">Still open</span>
                        <h2 class="section-title">Active notices</h2>
                        <p class="section-lede">These incidents are still active and appear at the top of the archive until they are resolved.</p>
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
                    <span class="status-eyebrow">Archive</span>
                    <h2 class="section-title">All published history</h2>
                    <p class="section-lede">A full archive of published incidents and maintenance updates, ordered with active notices first.</p>
                </div>
            </div>

            <div class="history-feed">
                @forelse ($incidentHistory as $incident)
                    <article class="history-entry">
                        <div class="history-entry__meta">
                            <x-status-badge :status="$incident['severity']" />
                            <span>{{ $incident['published_at'] ? \Illuminate\Support\Carbon::parse($incident['published_at'])->format('M j, Y g:i A T') : 'Published incident' }}</span>
                            <span>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $incident['status'])) }}</span>
                        </div>
                        <div class="history-entry__content">
                            <div>
                                <h3 class="history-title">
                                    <a href="{{ route('status.incidents.show', $incident['slug']) }}">{{ $incident['title'] }}</a>
                                </h3>
                                @if ($incident['latest_update'])
                                    <p class="section-lede">{{ \Illuminate\Support\Str::limit($incident['latest_update'], 240) }}</p>
                                @elseif ($incident['summary'])
                                    <p class="section-lede">{{ \Illuminate\Support\Str::limit($incident['summary'], 240) }}</p>
                                @endif
                            </div>
                            <a href="{{ route('status.incidents.show', $incident['slug']) }}" class="history-entry__link">View details</a>
                        </div>
                    </article>
                @empty
                    <article class="notice-card notice-card--quiet">
                        <div class="notice-card__meta">
                            <span>No published history yet</span>
                        </div>
                        <p class="section-lede">Published incidents and maintenance updates will appear here once the first notice is posted.</p>
                    </article>
                @endforelse
            </div>
        </section>
    </div>
</x-status-layout>
