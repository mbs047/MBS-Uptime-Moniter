# Getting Started

This guide installs the monitor application from this monorepo, boots it
locally, and walks through the first admin login and first smoke checks.

## Prerequisites

- PHP `8.3+`
- Composer `2.x`
- Node.js `22+`
- SQLite, MySQL, or PostgreSQL
- a mail sink or SMTP server if you want to test email flows

The default local environment in `apps/status/.env.example` uses:

- `sqlite` for the database
- `database` for sessions
- `database` for cache
- `database` for queue jobs
- `log` for mail

## Install The App

From the repository root:

```bash
cd apps/status
composer install
npm install
cp .env.example .env
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
```

If you prefer MySQL or PostgreSQL, update `apps/status/.env` before running the
migrations.

## Important Environment Values

Review these before the first boot:

- `APP_URL`
  Base URL used by the app and public links.
- `APP_SETUP_TOKEN`
  Required for the one-time first-admin bootstrap route.
- `DB_*`
  Database connection settings.
- `QUEUE_CONNECTION`
  Defaults to `database`.
- `CACHE_STORE`
  Defaults to `database`.
- `SESSION_DRIVER`
  Defaults to `database`.
- `MAIL_*`
  Mail transport and sender defaults.

## Start The Monitor

Run the application stack:

```bash
cd apps/status
composer run dev
```

This starts:

- the Laravel development server
- a queue listener
- log tailing
- the Vite development server

In another terminal, run the scheduler:

```bash
cd apps/status
php artisan schedule:work
```

The scheduler is required because it dispatches checks every minute and runs the
nightly uptime refresh and retention commands.

## Create The First Admin

The monitor does not ship with a default admin user. Bootstrap the first admin
through the setup flow:

1. Set `APP_SETUP_TOKEN` in `apps/status/.env`.
2. Visit `/admin/setup?token=YOUR_TOKEN`.
3. Submit the setup form.
4. Open the generated invite link.
5. Complete the invite acceptance flow.
6. Sign in at `/admin`.

After the first admin exists, `/admin/setup` is no longer available.

## First Things To Configure In Admin

Once signed in, visit the Filament admin panel and review:

- `Platform Settings`
  Brand name, public copy, support email, sender address, uptime window,
  retention, default thresholds, and probe registration token.
- `Services`
  Public groups such as API, Auth, Billing, Email.
- `Components`
  Public items inside a service.
- `Checks`
  Automated HTTP, SSL, DNS, and TCP checks.
- `Incidents`
  Draft, published, and resolved incidents with timeline updates.
- `Remote Integrations`
  Package-enabled Laravel applications imported from metadata or push
  registration.

## Local Smoke Checks

After setup, verify the main routes:

- `GET /`
- `GET /api/status/summary`
- `GET /api/status/services`
- `GET /api/status/incidents`
- `GET /admin`

Useful commands while verifying behavior:

```bash
cd apps/status
php artisan test
./vendor/bin/pint --test
npm run build
php artisan status:dispatch-due-checks
php artisan status:refresh-daily-uptime --days=2
php artisan status:prune-check-runs
```

## Typical First Monitor Setup

You can populate the monitor in two ways:

### Manual

1. Create a `Service`.
2. Create one or more `Components`.
3. Create `Checks` under those components.
4. Publish incidents manually when needed.

### Package-Enabled Laravel App

1. Install `mbs047/laravel-status-probe` inside the remote Laravel app.
2. Expose the remote app's health and metadata routes.
3. Connect it through `Remote Integrations` in the monitor.

See [monitoring-laravel-apps.md](monitoring-laravel-apps.md) for the full flow.

## Troubleshooting

### The public page loads but checks never change

Make sure the scheduler is running:

```bash
cd apps/status
php artisan schedule:work
```

Also confirm the queue listener is running. `composer run dev` starts one
locally.

### Admin setup says the token is invalid

Check that `APP_SETUP_TOKEN` is set in `apps/status/.env` and that the URL
matches exactly:

```text
/admin/setup?token=YOUR_TOKEN
```

### Emails are not being sent

For local development, the default mailer is `log`. Switch the `MAIL_*` values
to a real SMTP provider or a local mail sink such as Mailpit if you want to
observe real outbound messages.
