# Laravel Status Probe

`mbs047/laravel-status-probe` adds authenticated health and metadata endpoints
to a Laravel application so it can be monitored by an external status system.

This package is meant to be installed inside the app you want to monitor. It
does not create a public status page by itself. Instead, it exposes structured
probe data that a status monitor can import or register against.

## What It Does

- registers a health endpoint, default `GET /status/health`
- registers a metadata endpoint, default `GET /status/metadata`
- protects both endpoints with a bearer token by default
- exposes built-in probes for `app`, `db`, and `cache`
- supports optional heartbeat-driven probes for `queue` and `scheduler`
- can push its registration payload to a remote monitor
- supports custom contributors through a simple contract

## Requirements

- PHP `^8.2`
- Laravel `11`, `12`, or `13`
- a correct `APP_URL` value in the monitored application

## Installation

```bash
composer require mbs047/laravel-status-probe
php artisan status-probe:install
```

The install command will:

- publish `config/status-probe.php`
- seed missing `.env` values
- print the next steps for connecting to your monitor

## Quick Start

After installation, make sure these values exist in your `.env`:

```dotenv
STATUS_PROBE_APP_ID=
STATUS_PROBE_TOKEN=
STATUS_PROBE_HEALTH_PATH=status/health
STATUS_PROBE_METADATA_PATH=status/metadata
STATUS_MONITOR_URL=
STATUS_MONITOR_TOKEN=
```

Important notes:

- `STATUS_PROBE_TOKEN` protects the probe routes by default
- `APP_URL` should be set correctly so the metadata payload contains valid URLs
- `STATUS_MONITOR_URL` and `STATUS_MONITOR_TOKEN` are only needed if you want
  the monitored app to push its registration into a remote monitor

Once installed, your app will expose:

- `GET /status/health`
- `GET /status/metadata`

Both routes use the configured middleware stack plus the package auth
middleware. By default, that means the routes live in the `api` middleware
group and require a bearer token.

## Security

The default setup is intentionally private.

- bearer token auth is enabled by default
- requests without a valid token receive `401 Unauthorized`
- if the token is missing from config, the package returns `503` so the app
  does not silently expose an unprotected probe surface

Do not expose these endpoints publicly without understanding the tradeoff.
Even if the data looks harmless, the probe surface reveals internals about your
application runtime and infrastructure.

## Health Payload

The health endpoint returns a single JSON payload with an overall status and a
set of named checks:

```json
{
  "overall_status": "operational",
  "generated_at": "2026-03-22T12:00:00+00:00",
  "service": {
    "name": "Billing API",
    "slug": "billing-api",
    "description": "Internal billing application"
  },
  "checks": {
    "app": {
      "label": "Application",
      "description": "Laravel application runtime bootstrap health.",
      "status": "operational",
      "summary": "Laravel application booted successfully.",
      "details": {}
    },
    "db": {
      "label": "Database",
      "description": "Primary database connection round trip.",
      "status": "operational",
      "summary": "Database query round trip succeeded.",
      "details": {}
    }
  }
}
```

Supported status values:

- `operational`
- `degraded`
- `partial_outage`
- `major_outage`

If a contributor throws an exception, the package catches it and reports that
contributor as a `major_outage` instead of breaking the whole endpoint.

## Metadata Payload

The metadata endpoint returns the registration contract a monitor can use to
create services, components, and HTTP checks automatically.

It includes:

- stable `app_id`
- service name, slug, and description
- the public health and metadata URLs
- auth mode
- component definitions
- suggested HTTP check recipes for each component

Each component includes a `status_json_path` like
`checks.db.status`, which lets the monitor read one probe status from the
shared health payload.

## Connecting To A Monitor

There are two common ways to connect this package to a monitor.

### Pull Model

Your monitor fetches the metadata endpoint, reads the service/component
definitions, and creates checks against the shared health URL.

For this model, you usually only need:

- `STATUS_PROBE_TOKEN`
- a correct `APP_URL`

### Push Model

If your monitor accepts probe registrations, configure:

```dotenv
STATUS_MONITOR_URL=https://status.example.com
STATUS_MONITOR_TOKEN=your-monitor-token
```

Then run:

```bash
php artisan status-probe:register
```

That command posts the metadata payload to:

```text
POST {STATUS_MONITOR_URL}/api/integrations/probes/register
```

## Built-In Contributors

Enabled by default:

- `app`: verifies the Laravel app booted successfully
- `db`: performs a database round trip with `select 1`
- `cache`: performs a cache write/read/delete cycle

Available but disabled by default:

- `queue`: checks freshness of a queue heartbeat written from the queue loop
- `scheduler`: checks freshness of a scheduler heartbeat written from a command

You can toggle these in `config/status-probe.php`:

```php
'contributors' => [
    'app' => true,
    'db' => true,
    'cache' => true,
    'queue' => true,
    'scheduler' => true,
],
```

## Queue And Scheduler Heartbeats

### Scheduler

Enable the `scheduler` contributor, then record a heartbeat every minute from
your Laravel scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('status-probe:heartbeat scheduler')->everyMinute();
```

The scheduler contributor reports a failure if the heartbeat becomes older than
`heartbeat.scheduler_max_age_seconds`.

### Queue

Enable the `queue` contributor and run a normal Laravel queue worker. The
package listens to the worker loop and refreshes a queue heartbeat while the
worker is alive.

The queue contributor reports a failure if the heartbeat becomes older than
`heartbeat.queue_max_age_seconds`.

For multi-server or container deployments, use a shared cache store for
heartbeat data if your queue workers and HTTP app instances do not share local
state.

## Configuration

The published config file is `config/status-probe.php`.

Main options:

- `app_id`: stable identifier for the monitored app
- `service_name`, `service_slug`, `service_description`: service metadata
- `health_path`, `metadata_path`: route paths
- `middleware`: middleware stack used for the probe routes
- `auth.mode`, `auth.token`: route auth configuration
- `monitor.url`, `monitor.token`: remote monitor registration settings
- `monitor.interval_minutes`, `timeout_seconds`, `failure_threshold`,
  `recovery_threshold`: defaults exposed in metadata for monitor-created checks
- `heartbeat.store`: optional cache store for heartbeats
- `heartbeat.queue_max_age_seconds`, `heartbeat.scheduler_max_age_seconds`:
  stale-heartbeat thresholds

## Custom Contributors

You can add your own contributors for app-specific dependencies such as search,
object storage, Redis, or external upstreams.

Implement `Mbs047\LaravelStatusProbe\Contracts\ProbeContributor`, bind it in
the container, and tag it with `status-probe.contributors`:

```php
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

$this->app->singleton(SearchProbe::class, function () {
    return new class implements ProbeContributor
    {
        public function key(): string
        {
            return 'search';
        }

        public function label(): string
        {
            return 'Search';
        }

        public function description(): ?string
        {
            return 'Search cluster availability.';
        }

        public function defaultCheckConfig(): array
        {
            return [];
        }

        public function resolve(): ProbeResult
        {
            return new ProbeResult(ProbeStatus::Operational, 'Search is healthy.');
        }
    };
});

$this->app->tag(SearchProbe::class, 'status-probe.contributors');
```

Custom contributors automatically appear in both:

- the health payload
- the metadata payload used for registration/import

## Commands

- `php artisan status-probe:install`
  Publishes config and seeds missing environment values.
- `php artisan status-probe:register`
  Pushes the registration payload to the configured monitor.
- `php artisan status-probe:heartbeat scheduler`
  Records a scheduler heartbeat timestamp.

## Testing

```bash
composer test
```

## License

MIT
