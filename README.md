# MBS Uptime Monitor

[![CI](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml/badge.svg)](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/github/license/mbs047/MBS-Uptime-Moniter)](LICENSE)

MBS Uptime Monitor is a self-hosted status page and uptime monitoring platform
built with Laravel, Filament, and Livewire. It combines a public status site, a
private operations panel, automated checks, incident publishing, subscriber
notifications, and a Laravel probe package for monitored applications.

This repository is a workspace repo. The monitor application lives in
`apps/status`, while the repository root is reserved for shared documentation,
API contracts, GitHub automation, and package publishing support.

## What This Repository Contains

- `apps/status`
  The main monitor application with the public status page, Filament admin
  panel, API endpoints, incident workflow, remote integrations, and check
  engine.
- `packages/laravel-status-probe`
  The Composer package that installs authenticated health and metadata endpoints
  inside another Laravel app so it can connect to the monitor.
- `api/status`
  Public API docs, schema, and example payloads for the monitor app.
- `documents`
  Long-form documentation for installation, deployment, operations, and
  integration workflows.

## Product Capabilities

- public status overview at `/`
- incident detail pages with update timelines
- Filament admin panel at `/admin`
- automated checks for `http`, `ssl`, `dns`, and `tcp`
- manual incident workflow with published and resolved timelines
- subscriber confirmation and unsubscribe flows
- daily uptime aggregation for 90-day reporting
- remote integration support for package-enabled Laravel apps
- public JSON endpoints for summary, services, incidents, and subscriber signups

## Workspace Layout

```text
.
├── api/status/                  # API contract, OpenAPI schema, example payloads
├── apps/status/                 # Laravel monitor application
├── documents/                   # Long-form documentation
├── packages/laravel-status-probe/ # Composer package for monitored Laravel apps
├── README.md                    # Repository entrypoint
└── .github/                     # CI, workflows, issue and PR templates
```

## Documentation Map

Start here depending on what you are trying to do:

- [documents/getting-started.md](documents/getting-started.md)
  Install the monitor locally, boot the app, and create the first admin.
- [documents/monitoring-laravel-apps.md](documents/monitoring-laravel-apps.md)
  Connect other Laravel apps by using the probe package or manual checks.
- [documents/deployment-and-operations.md](documents/deployment-and-operations.md)
  Production deployment, cron, queue workers, mail, upgrades, and operational
  runbooks.
- [documents/architecture.md](documents/architecture.md)
  Domain model, status precedence, check engine behavior, and integration
  architecture.
- [api/status/README.md](api/status/README.md)
  Public API reference and example payloads.
- [packages/laravel-status-probe/README.md](packages/laravel-status-probe/README.md)
  Public package README for the monitored-app probe package.

The package is also published from its own repository:

- [MBS-Uptime-Moniter-Package](https://github.com/mbs047/MBS-Uptime-Moniter-Package)

## Quick Start

### Prerequisites

- PHP `8.3+`
- Composer `2.x`
- Node.js `22+`
- SQLite, MySQL, or PostgreSQL

### Local Install

```bash
make status-install
cp apps/status/.env.example apps/status/.env
[ -f apps/status/database/database.sqlite ] || touch apps/status/database/database.sqlite
make status-artisan CMD="key:generate"
make status-artisan CMD="migrate --seed"
make status-dev
```

In a second terminal, run the scheduler:

```bash
make status-artisan CMD="schedule:work"
```

`make status-dev` already starts:

- the Laravel development server
- a queue listener
- log tailing
- the Vite dev server

### First Admin Bootstrap

The first admin invite is protected by `APP_SETUP_TOKEN`.

1. Set `APP_SETUP_TOKEN` in `apps/status/.env`.
2. Start the app.
3. Open `/admin/setup?token=YOUR_TOKEN`.
4. Submit the setup form.
5. Complete the invite flow and sign in at `/admin`.

Once the first admin exists, the setup route disables itself.

## Public Endpoints

### Web Routes

- `GET /`
- `GET /incidents/{incident:slug}`
- `GET /status/subscribers/confirm/{token}`
- `GET /status/subscribers/unsubscribe/{token}`

### Public API Routes

- `GET /api/status/summary`
- `GET /api/status/services`
- `GET /api/status/incidents`
- `POST /api/status/subscribers`

### Private Integration Route

- `POST /api/integrations/probes/register`

This private route is for package-enabled Laravel apps that push their
registration payload into the monitor. It requires a bearer token configured in
the monitor's Platform Settings.

## Working From The Repo Root

Use the root `Makefile` to work with the nested Laravel app and package without
changing directories:

```bash
make status-install
make status-dev
make status-test
make status-build
make status-pint
make status-artisan CMD="migrate --seed"
make probe-install
make probe-test
make probe-composer CMD="update --dry-run"
```

## Status Monitor and Probe Package

The monitor and the package are designed to work together:

- install the monitor from this repository
- install `mbs047/laravel-status-probe` inside each Laravel app you want to
  monitor
- let the monitor either pull metadata from the app or accept a pushed
  registration payload
- the monitor creates one service and one or more component checks from the
  package metadata

For the full walkthrough, see:

- [documents/monitoring-laravel-apps.md](documents/monitoring-laravel-apps.md)
- [packages/laravel-status-probe/docs/connecting-to-monitor.md](packages/laravel-status-probe/docs/connecting-to-monitor.md)

## Testing and Quality

Before opening a pull request, run:

```bash
make status-pint
make status-test
make status-build
make probe-test
```

## Deployment Note

If you deploy the monitor app from this monorepo, the web root must point to:

```text
apps/status/public
```

Do not point your web server at the repository root.

## Package Publishing Note

The package subtree at `packages/laravel-status-probe` is published to the
package-facing repository so Packagist can read a root-level `composer.json`
there. The split-publish workflow supports both automatic publishing from
`main` and manual dispatch from GitHub Actions.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please review [SECURITY.md](SECURITY.md) for vulnerability reporting
instructions.

## Support

Support expectations are documented in [SUPPORT.md](SUPPORT.md).

## License

This project is licensed under the [MIT License](LICENSE).
