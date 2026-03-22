# Deployment and Operations

This guide covers production deployment and the operational routines required to
keep the monitor healthy.

## Production Architecture

The monitor is a Laravel application located at:

```text
apps/status
```

Your production web root must point to:

```text
apps/status/public
```

The repository root itself is not a deployable Laravel document root.

## Minimum Production Services

- PHP `8.3+`
- Composer `2.x`
- Node.js `22+` for asset builds
- SQLite, MySQL, or PostgreSQL
- a process manager for the queue worker
- cron or an equivalent scheduler trigger
- a mail provider if you want subscriber notifications

## Required Runtime Responsibilities

The monitor needs all of these:

- web server serving `apps/status/public`
- queue worker processing the database queue
- Laravel scheduler running every minute
- database connectivity
- mail transport for subscriber and invite emails

## Environment Checklist

At minimum, review these values in `apps/status/.env`:

- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_SETUP_TOKEN`
- `DB_CONNECTION` and related `DB_*`
- `QUEUE_CONNECTION`
- `CACHE_STORE`
- `SESSION_DRIVER`
- `MAIL_*`

Recommended production choices:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=database` unless you intentionally switch to another driver
- real SMTP or API mail transport instead of `log`

## Deploying The App

A typical deploy sequence inside `apps/status` looks like:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
```

If you deploy from the repository root, use the root wrappers where helpful:

```bash
make status-build
make status-artisan CMD="migrate --force"
```

## First Production Bootstrap

1. Deploy the application.
2. Set a strong `APP_SETUP_TOKEN`.
3. Visit `/admin/setup?token=YOUR_TOKEN`.
4. Generate the first admin invite.
5. Complete the invite flow.
6. Sign in to `/admin`.
7. Configure `Platform Settings`.

After the first admin exists, remove or rotate the bootstrap token value as part
of your security process even though the route is already self-disabling.

## Scheduler

The application schedule is defined in `apps/status/routes/console.php`.

Current scheduled tasks:

- `status:dispatch-due-checks` every minute
- `status:refresh-daily-uptime --days=2` daily at `00:05`
- `status:prune-check-runs` daily at `00:15`

Typical cron entry:

```cron
* * * * * cd /path/to/repo/apps/status && php artisan schedule:run >> /dev/null 2>&1
```

If you prefer `schedule:work`, run it under a process manager.

## Queue Worker

The default queue driver is `database`, so a worker must always be running in
production.

Typical worker command:

```bash
php artisan queue:work --tries=1 --timeout=0
```

Run it under Supervisor, systemd, or your platform's worker manager.

Without a queue worker:

- checks will not execute after dispatch
- status rollups will lag
- subscriber mail will not send

## Mail

Mail is used for:

- admin invites
- subscriber confirmation
- incident created, updated, and resolved notifications

Configure:

- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Also configure `mail_from_name` and `mail_from_address` in `Platform Settings`
so the app-level settings match your public sender identity.

## Platform Settings You Should Review

In the admin panel, open `Platform Settings` and verify:

- brand name and tagline
- support email
- sender name and sender address
- SEO title and description
- uptime window days
- raw run retention days
- default failure and recovery thresholds
- `probe_registration_token` if you want monitored Laravel apps to push
  registrations

## Remote Integrations In Production

If you are monitoring package-enabled Laravel apps:

- use HTTPS for both health and metadata endpoints
- set a strong monitor-side `probe_registration_token`
- store remote bearer tokens only inside the monitor admin
- verify the monitor host can reach the remote apps over the network

## Operational Commands

Useful maintenance commands:

```bash
php artisan status:dispatch-due-checks
php artisan status:refresh-daily-uptime --days=2
php artisan status:prune-check-runs
php artisan route:list
php artisan about
```

From the repo root:

```bash
make status-artisan CMD="status:dispatch-due-checks"
make status-artisan CMD="status:refresh-daily-uptime --days=2"
make status-artisan CMD="status:prune-check-runs"
```

## Backups and Data Retention

The app stores:

- raw `check_runs`
- aggregated `component_daily_uptimes`
- incidents and incident updates
- subscribers
- remote integrations

Defaults:

- raw runs retained for `14` days
- uptime window defaults to `90` days

Back up the database as part of your normal production backup policy.

## Upgrades

Before upgrading:

1. back up the database
2. pull the new code
3. install Composer dependencies
4. build frontend assets
5. run migrations
6. restart queue workers if your platform requires it
7. verify the public page and `/admin`

Recommended verification routes:

- `/`
- `/api/status/summary`
- `/api/status/services`
- `/api/status/incidents`
- `/admin`

## Troubleshooting

### Checks are being dispatched but no new results appear

The queue worker is likely not running.

### Status page is stale

Check both:

- scheduler
- queue worker

### Package-enabled apps stop syncing

Check:

- remote bearer token
- monitor-side `probe_registration_token` if using push registration
- network reachability to the remote metadata endpoint
- whether the remote app's `APP_URL` still matches reality

### Emails are not arriving

Verify:

- mail transport credentials
- sender address
- queue worker
- subscriber confirmation state

## Further Reading

- [getting-started.md](getting-started.md)
- [monitoring-laravel-apps.md](monitoring-laravel-apps.md)
- [architecture.md](architecture.md)
