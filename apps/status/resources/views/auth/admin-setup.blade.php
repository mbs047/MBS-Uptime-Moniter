<x-status-layout title="Admin setup" description="Bootstrap the first admin invitation">
    <div class="status-page auth-wrap">
        <section class="status-card auth-card">
            <span class="brand-kicker">Initial setup</span>
            <h1 class="auth-title">Create the first admin invitation.</h1>
            <p class="muted">
                This page only works until the first admin account exists. Enter the one-time setup token and the initial operator email.
            </p>

            <form method="POST" action="{{ route('admin.setup.store') }}" class="form-stack">
                @csrf
                <div>
                    <label class="field-label" for="setup_token">Setup token</label>
                    <input id="setup_token" name="setup_token" class="field-input" value="{{ old('setup_token', $setupToken) }}" required>
                </div>
                <div>
                    <label class="field-label" for="name">Display name</label>
                    <input id="name" name="name" class="field-input" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="field-input" value="{{ old('email') }}" required>
                </div>
                <button class="button-primary" type="submit">Create invite</button>
            </form>
        </section>
    </div>
</x-status-layout>
