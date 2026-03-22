# MBS Uptime Monitor

[![CI](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml/badge.svg)](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/github/license/mbs047/MBS-Uptime-Moniter)](LICENSE)

MBS Uptime Monitor is a self-hosted uptime monitoring and status page application built with Laravel, Filament, and Livewire. It gives teams a branded public status page, operational APIs, incident publishing tools, subscriber notifications, and automated service health rollups in one codebase.

This repository is organized as a workspace. The Laravel application now lives in `apps/status`, while the repo root is reserved for shared docs, API contracts, automation, and future top-level folders.

## Highlights

- Public status page with service, component, incident, and uptime history views
- Automated check drivers for HTTP, SSL, DNS, and TCP monitoring
- Companion Laravel probe package for automatic monitored-app registration
- Incident publishing workflow with updates, timelines, and severity mapping
- Subscriber confirmation and unsubscribe flows for status notifications
- Filament-powered admin panel for services, components, checks, incidents, and settings
- Daily uptime aggregation and raw check retention jobs for long-term reporting
- JSON endpoints for summary, services, incidents, and subscriber signups

## Stack

- Laravel 13
- PHP 8.3+
- Filament 5
- Livewire 4
- Vite
- Tailwind CSS 4
- PHPUnit 12

## Workspace Layout

```text
.
├── api/status/          # Endpoint contracts, specs, and examples
├── apps/status/         # Laravel application
├── documents/           # Long-form repository documentation
├── packages/            # Composer-installable workspace packages
├── README.md            # Repository entrypoint
└── .github/             # CI, templates, and repo automation
```

## Quick Start

```bash
make status-install
cp apps/status/.env.example apps/status/.env
[ -f apps/status/database/database.sqlite ] || touch apps/status/database/database.sqlite
make status-artisan CMD="key:generate"
make status-artisan CMD="migrate --seed"
make status-dev
```

In a second terminal, run the scheduler so due checks and uptime maintenance jobs continue to execute locally:

```bash
make status-artisan CMD="schedule:work"
```

## First Admin Setup

The initial admin bootstrap is protected by `APP_SETUP_TOKEN`.

1. Set a strong `APP_SETUP_TOKEN` value in `apps/status/.env`.
2. Start the app locally or deploy it.
3. Visit `/admin/setup?token=YOUR_TOKEN`.
4. Submit the bootstrap form to generate the first admin invite.
5. Complete the invite flow and sign in at `/admin`.

Once the first admin account exists, the bootstrap route is no longer available.

## Available Endpoints

### Public web routes

- `/` - public status page
- `/incidents/{incident:slug}` - incident detail page
- `/status/subscribers/confirm/{token}` - subscriber confirmation
- `/status/subscribers/unsubscribe/{token}` - subscriber unsubscribe

### Public API routes

- `GET /api/status/summary`
- `GET /api/status/services`
- `GET /api/status/incidents`
- `POST /api/status/subscribers`

## Root Commands

Use the root `Makefile` to work with the nested Laravel app without leaving the repository root:

- `make status-install`
- `make status-dev`
- `make status-test`
- `make status-build`
- `make status-pint`
- `make status-artisan CMD="migrate --seed"`
- `make probe-install`
- `make probe-test`
- `make probe-composer CMD="update --dry-run"`

## Local Development

All application runtime files live under `apps/status`, including:

- `apps/status/.env`
- `apps/status/database/database.sqlite`
- `apps/status/public`
- `apps/status/routes/api.php`

The repository includes a root wrapper that starts the Laravel app, queue worker, log tailing, and Vite dev server together:

```bash
make status-dev
```

Useful additional commands:

```bash
make status-pint
make status-test
make status-build
make status-artisan CMD="status:dispatch-due-checks"
make status-artisan CMD="status:refresh-daily-uptime --days=2"
make status-artisan CMD="status:prune-check-runs"
make probe-install
make probe-test
```

## Testing and Quality

Before opening a pull request, run:

```bash
make status-pint
make status-test
make status-build
make probe-test
```

The repository also includes GitHub Actions CI and Dependabot configuration for ongoing maintenance.

## Laravel Probe Package

The workspace now includes a Composer package at `packages/laravel-status-probe`.

- Package name: `mbs047/laravel-status-probe`
- Purpose: install authenticated health and metadata endpoints into another Laravel app
- Local package commands:
  - `make probe-install`
  - `make probe-test`
  - `make probe-composer CMD="update --dry-run"`

Once installed in another Laravel app, the package provides:

- a configurable health endpoint, default `status/health`
- a configurable metadata endpoint, default `status/metadata`
- `php artisan status-probe:install`
- `php artisan status-probe:register`
- `php artisan status-probe:heartbeat scheduler`

## API Docs

Repository-level API docs and examples live in `api/status/`.

## Deployment

If your deployment previously assumed the Laravel app lived at the repo root, update it to use `apps/status/public` as the web root.

The package split workflow publishes `packages/laravel-status-probe` to its package-facing repository so it can be registered with Packagist independently of the monorepo root.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please review [SECURITY.md](SECURITY.md) for vulnerability reporting instructions.

## Support

Support guidance and repository communication expectations are documented in [SUPPORT.md](SUPPORT.md).

## License

This project is licensed under the [MIT License](LICENSE).
