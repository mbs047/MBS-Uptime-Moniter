<x-status-layout :title="$incident->title.' · '.config('app.name')" :description="$incident->summary">
    @php
        $publishedAt = $incident->published_at ?? $incident->created_at;
        $affectedCount = $incident->services->count() + $incident->components->count();
    @endphp

    <div class="status-page status-page--detail">
        <header class="status-header">
            <div class="status-brand">
                <span class="status-eyebrow">Incident detail</span>
                <a href="{{ route('status.index') }}" class="status-brand__name">Return to status overview</a>
                <p class="status-brand__tagline">{{ $incident->title }}</p>
            </div>

            <nav class="status-nav" aria-label="Incident page">
                <a href="{{ route('status.history') }}" class="status-nav__link">View history</a>
                <span class="status-nav__stamp">{{ $incident->status->label() }}</span>
            </nav>
        </header>

        <section class="detail-hero">
            <div class="detail-hero__main">
                <span class="status-eyebrow">Current notice</span>
                <h1 class="detail-title">{{ $incident->title }}</h1>
                <p class="status-copy">{{ $incident->summary ?: 'Timeline and status updates for this incident.' }}</p>

                <div class="status-meta-row">
                    <x-status-badge :status="$incident->severity->value" />
                    <span>{{ $incident->status->label() }}</span>
                    <span>Published {{ optional($publishedAt)->diffForHumans() }}</span>
                    @if ($incident->resolved_at)
                        <span>Resolved {{ optional($incident->resolved_at)->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            <aside class="detail-hero__aside">
                <div class="detail-stat">
                    <span>Published</span>
                    <strong>{{ optional($publishedAt)->format('M j, Y') }}</strong>
                </div>
                <div class="detail-stat">
                    <span>Updates</span>
                    <strong>{{ $incident->updates->count() }}</strong>
                </div>
                <div class="detail-stat">
                    <span>Affected systems</span>
                    <strong>{{ $affectedCount }}</strong>
                </div>
            </aside>
        </section>

        <section class="status-section">
            <div class="status-section__header">
                <div>
                    <span class="status-eyebrow">Scope</span>
                    <h2 class="section-title">Affected services and components</h2>
                    <p class="section-lede">These systems are included in the published incident scope.</p>
                </div>
            </div>

            <div class="detail-targets">
                @foreach ($incident->services as $service)
                    <span class="status-pill-link">Service: {{ $service->name }}</span>
                @endforeach
                @foreach ($incident->components as $component)
                    <span class="status-pill-link">Component: {{ $component->display_name }}</span>
                @endforeach
            </div>
        </section>

        <section class="status-section">
            <div class="status-section__header">
                <div>
                    <span class="status-eyebrow">Timeline</span>
                    <h2 class="section-title">Published updates</h2>
                    <p class="section-lede">The most recent operational updates for this incident appear below in chronological order.</p>
                </div>
            </div>

            <div class="timeline">
                @forelse ($incident->updates as $update)
                    <article class="timeline-entry">
                        <div class="timeline-entry__rail">
                            <span class="timeline-entry__dot"></span>
                        </div>

                        <div class="timeline-entry__body">
                            <div class="timeline-meta">
                                @if ($update->status)
                                    <x-status-badge :status="$update->status->value === 'published' ? $incident->severity->value : 'degraded'" />
                                @endif
                                <span>{{ optional($update->published_at ?? $update->created_at)->format('M j, Y g:i A T') }}</span>
                            </div>

                            @if ($update->title)
                                <h3 class="timeline-title">{{ $update->title }}</h3>
                            @endif

                            <p class="section-lede">{{ $update->body }}</p>
                        </div>
                    </article>
                @empty
                    <article class="timeline-entry timeline-entry--empty">
                        <div class="timeline-entry__body">
                            <p class="section-lede">No published updates have been posted for this incident yet.</p>
                        </div>
                    </article>
                @endforelse
            </div>
        </section>
    </div>
</x-status-layout>
