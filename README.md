# MBS Uptime Monitor

[![CI](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml/badge.svg)](https://github.com/mbs047/MBS-Uptime-Moniter/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/github/license/mbs047/MBS-Uptime-Moniter)](LICENSE)

MBS Uptime Monitor is a self-hosted uptime monitoring and status page application built with Laravel, Filament, and Livewire. It gives teams a branded public status page, operational APIs, incident publishing tools, subscriber notifications, and automated service health rollups in one codebase.

## Highlights

- Public status page with service, component, incident, and uptime history views
- Automated check drivers for HTTP, SSL, DNS, and TCP monitoring
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

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --seed
npm install
composer run dev
```

In a second terminal, run the scheduler so due checks and uptime maintenance jobs continue to execute locally:

```bash
php artisan schedule:work
```

## First Admin Setup

The initial admin bootstrap is protected by `APP_SETUP_TOKEN`.

1. Set a strong `APP_SETUP_TOKEN` value in `.env`.
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

## Local Development

The repository includes a convenience script that starts the Laravel app, queue worker, log tailing, and Vite dev server together:

```bash
composer run dev
```

Useful additional commands:

```bash
php artisan test
./vendor/bin/pint --test
php artisan status:dispatch-due-checks
php artisan status:refresh-daily-uptime --days=2
php artisan status:prune-check-runs
```

## Testing and Quality

Before opening a pull request, run:

```bash
./vendor/bin/pint --test
php artisan test
npm run build
```

The repository also includes GitHub Actions CI and Dependabot configuration for ongoing maintenance.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please review [SECURITY.md](SECURITY.md) for vulnerability reporting instructions.

## Support

Support guidance and repository communication expectations are documented in [SUPPORT.md](SUPPORT.md).

## License

This project is licensed under the [MIT License](LICENSE).
