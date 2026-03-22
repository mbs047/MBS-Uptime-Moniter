<x-status-layout title="Accept admin invitation" description="Set your admin credentials">
    <div class="status-page auth-wrap">
        <section class="status-card auth-card">
            <span class="brand-kicker">Admin invitation</span>
            <h1 class="auth-title">Set your admin credentials.</h1>
            <p class="muted">
                You are accepting an invitation for <strong>{{ $invite->email }}</strong>. Once submitted, you will be signed in to the admin panel.
            </p>

            <form method="POST" action="{{ route('admin.invites.store', $invite->token) }}" class="form-stack">
                @csrf
                <div>
                    <label class="field-label" for="name">Display name</label>
                    <input id="name" name="name" class="field-input" value="{{ old('name', $invite->name) }}" required>
                </div>
                <div>
                    <label class="field-label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="field-input" required>
                </div>
                <div>
                    <label class="field-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field-input" required>
                </div>
                <button class="button-primary" type="submit">Activate admin access</button>
            </form>
        </section>
    </div>
</x-status-layout>
