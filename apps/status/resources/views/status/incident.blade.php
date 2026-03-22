<x-status-layout :title="$incident->title.' · '.config('app.name')" :description="$incident->summary">
    <div class="status-page">
        <header class="status-topbar">
            <div class="brand-mark">
                <span class="brand-kicker">Incident detail</span>
                <strong class="brand-name">{{ $incident->title }}</strong>
                <span class="status-subtle">
                    <a href="{{ route('status.index') }}">Return to status overview</a>
                </span>
            </div>

            <x-status-badge :status="$incident->severity->value" />
        </header>

        <section class="status-card status-card--hero">
            <div class="status-hero-grid">
                <div>
                    <span class="brand-kicker">Summary</span>
                    <h1 class="status-display" style="font-size: clamp(2.2rem, 5vw, 4.2rem); max-width: 14ch;">
                        {{ $incident->summary ?: 'Timeline and status updates for this incident.' }}
                    </h1>
                </div>

                <div class="kpi-stack">
                    <div class="kpi-item">
                        <span class="kpi-label">Published</span>
                        <span class="kpi-value">{{ optional($incident->published_at)->format('M j') }}</span>
                    </div>
                    <div class="kpi-item">
                        <span class="kpi-label">Status</span>
                        <span class="kpi-value">{{ $incident->status->label() }}</span>
                    </div>
                </div>
            </div>

            <div class="inline-cluster" style="margin-top: 1rem;">
                @foreach ($incident->services as $service)
                    <span class="button-secondary" style="padding: 0.45rem 0.8rem;">Service: {{ $service->name }}</span>
                @endforeach
                @foreach ($incident->components as $component)
                    <span class="button-secondary" style="padding: 0.45rem 0.8rem;">Component: {{ $component->display_name }}</span>
                @endforeach
            </div>
        </section>

        <section class="status-card status-card--section" style="margin-top: 1.35rem;">
            <span class="brand-kicker">Timeline</span>
            <h2 class="section-title">Published updates</h2>
            <div class="timeline-list">
                @forelse ($incident->updates as $update)
                    <article class="timeline-item">
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
                    </article>
                @empty
                    <article class="timeline-item">
                        <p class="section-lede">No published updates have been posted for this incident yet.</p>
                    </article>
                @endforelse
            </div>
        </section>
    </div>
</x-status-layout>
