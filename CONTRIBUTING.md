# Contributing

Thanks for your interest in improving MBS Uptime Monitor.

## Before You Start

- Search existing issues and pull requests before opening a new one.
- Open an issue first for major changes so the direction can be aligned before implementation starts.
- Keep pull requests focused. Small, reviewable changes merge faster and are easier to validate.

## Local Setup

```bash
make status-install
cp apps/status/.env.example apps/status/.env
[ -f apps/status/database/database.sqlite ] || touch apps/status/database/database.sqlite
make status-artisan CMD="key:generate"
make status-artisan CMD="migrate --seed"
make status-dev
```

Run the scheduler in a separate terminal when working on monitoring and uptime aggregation features:

```bash
make status-artisan CMD="schedule:work"
```

## Development Expectations

- Follow the existing Laravel and Filament project structure inside `apps/status`.
- Add or update tests for behavior changes when practical.
- Keep code style consistent with Laravel conventions and `pint`.
- Update documentation when setup steps, API behavior, or product capabilities change.
- Do not mix unrelated refactors into the same pull request.

## Quality Checks

Run these before submitting a pull request:

```bash
make status-pint
make status-test
make status-build
```

## Pull Request Checklist

- Explain what changed and why.
- Link the relevant issue when one exists.
- Note any migrations, environment changes, or breaking changes.
- Include screenshots or recordings for UI changes when they help reviewers.
- Confirm the quality checks above passed locally.

## Commit Guidance

Use clear, imperative commit messages that describe the behavior change. A good commit title should make sense when read on its own in the project history.

## Security

If you discover a security issue, do not open a public issue. Follow the private reporting process in [SECURITY.md](SECURITY.md).
