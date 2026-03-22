# Installation

This guide installs `mbs047/laravel-status-probe` into a Laravel application
that you want to monitor.

## Requirements

- PHP `^8.2`
- Laravel `11`, `12`, or `13`
- a valid `APP_URL`

## Install

```bash
composer require mbs047/laravel-status-probe
php artisan status-probe:install
```

The install command publishes `config/status-probe.php` and appends missing
environment values to your `.env` file.

## Default Environment Values

The install command seeds these keys when they are missing:

```dotenv
STATUS_PROBE_APP_ID=
STATUS_PROBE_TOKEN=
STATUS_PROBE_HEALTH_PATH=status/health
STATUS_PROBE_METADATA_PATH=status/metadata
STATUS_MONITOR_URL=
STATUS_MONITOR_TOKEN=
```

## What Gets Registered

The package auto-discovers its service provider and registers:

- `GET /status/health`
- `GET /status/metadata`
- `php artisan status-probe:install`
- `php artisan status-probe:register`
- `php artisan status-probe:heartbeat scheduler`

## Auth Behavior

The probe routes are protected by bearer token auth by default.

Expected request header:

```text
Authorization: Bearer YOUR_STATUS_PROBE_TOKEN
```

Behavior:

- `401` when the token is missing or invalid
- `503` when auth mode is bearer but no token is configured

## Configuration

The published config file is:

```text
config/status-probe.php
```

Main options:

- `app_id`
- `service_name`
- `service_slug`
- `service_description`
- `health_path`
- `metadata_path`
- `middleware`
- `auth.mode`
- `auth.token`
- `monitor.url`
- `monitor.token`
- `monitor.interval_minutes`
- `monitor.timeout_seconds`
- `monitor.failure_threshold`
- `monitor.recovery_threshold`
- `heartbeat.store`
- `heartbeat.queue_max_age_seconds`
- `heartbeat.scheduler_max_age_seconds`
- `contributors`

## Route Customization

You can change the default paths:

```dotenv
STATUS_PROBE_HEALTH_PATH=healthz
STATUS_PROBE_METADATA_PATH=status/probe
```

You can also change the middleware stack in `config/status-probe.php`.

## APP_URL Matters

The metadata payload uses `APP_URL` when building:

- `base_url`
- `health_url`
- `metadata_url`

Set `APP_URL` correctly or your monitor may import invalid endpoint URLs.

## Testing The Endpoints

Example health request:

```bash
curl \
  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN" \
  https://your-app.example.com/status/health
```

Example metadata request:

```bash
curl \
  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN" \
  https://your-app.example.com/status/metadata
```

## Built-In Contributors

Enabled by default:

- `app`
- `db`
- `cache`

Available but disabled by default:

- `queue`
- `scheduler`

Toggle them in `config/status-probe.php`.

## Scheduler Heartbeat

To monitor the scheduler itself:

1. enable the `scheduler` contributor
2. add the heartbeat command to your scheduler

Example:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('status-probe:heartbeat scheduler')->everyMinute();
```

## Queue Heartbeat

To monitor queue workers:

1. enable the `queue` contributor
2. run a normal Laravel queue worker

The package refreshes the queue heartbeat during the worker loop.

If your workers and HTTP app instances do not share local cache state, configure
`heartbeat.store` to use a shared cache backend.

## Next Step

Once installation is complete, connect the app to your monitor:

- [connecting-to-monitor.md](connecting-to-monitor.md)
