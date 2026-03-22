# Monitoring Laravel Apps

This guide explains how to connect another Laravel application to the monitor.

There are two supported approaches:

- manual checks created directly inside the monitor
- automatic integration through `mbs047/laravel-status-probe`

The package approach is recommended for Laravel applications because it keeps
the monitored app self-describing and makes service/component creation much
easier.

## Option 1: Manual Checks

Use manual checks when:

- the target app is not Laravel
- you do not want to install the package
- you only need a few basic health checks

From the monitor admin panel:

1. Create a `Service`.
2. Create one or more `Components`.
3. Create `Checks` under those components.

Supported check types:

- `http`
- `ssl`
- `dns`
- `tcp`

Typical production setup for a web app:

- an HTTP health endpoint check
- an SSL certificate check
- a TCP port `443` reachability check
- a DNS resolution check for the application domain

## Option 2: Probe Package Integration

This is the preferred path for Laravel applications.

Install the package inside the monitored app:

```bash
composer require mbs047/laravel-status-probe
php artisan status-probe:install
```

The package exposes:

- a health endpoint, default `GET /status/health`
- a metadata endpoint, default `GET /status/metadata`

These routes are bearer-token protected by default.

## Pull Integration Flow

In a pull flow, the monitor reads metadata from the monitored app and creates
its local service, components, and checks from that payload.

### On The Monitored App

1. Install the package.
2. Set `APP_URL` correctly.
3. Set `STATUS_PROBE_TOKEN` in `.env`.
4. Confirm that `GET /status/metadata` returns a valid payload when called with
   the bearer token.

### On The Monitor App

1. Sign in to `/admin`.
2. Open `Remote Integrations`.
3. Create a new integration.
4. Set:
   - `base_url`
   - optional `metadata_url`
   - optional `health_url`
   - `auth_mode`
   - `auth_secret`
5. Save the record.
6. Use `Sync now`.

What the monitor does on sync:

- fetches the remote metadata payload
- creates or updates one local `Service`
- creates or updates local `Components`
- creates or updates one HTTP `Check` per component
- stores the remote token encrypted for future health and metadata requests

By default, each generated check points to the shared remote health endpoint and
reads a component status from a `status_json_path` such as `checks.db.status`.

## Push Integration Flow

In a push flow, the monitored app registers itself with the monitor by calling a
private monitor endpoint.

### On The Monitor App

1. Sign in to `/admin`.
2. Open `Platform Settings`.
3. Set `probe_registration_token`.

This token protects:

```text
POST /api/integrations/probes/register
```

### On The Monitored App

Set these values:

```dotenv
STATUS_MONITOR_URL=https://status.example.com
STATUS_MONITOR_TOKEN=your-monitor-probe-registration-token
```

Then run:

```bash
php artisan status-probe:register
```

What happens next:

- the monitored app posts its metadata payload to the monitor
- the monitor upserts the remote integration using `app_id`
- the monitor creates or updates the linked service, components, and checks
- future pull syncs can still be used if the integration is in hybrid mode

## Hybrid Integration Flow

The monitor stores integrations with a sync mode of `hybrid` by default.

In practice, that means:

- the monitored app can push registration payloads
- the monitor can still pull metadata later through `Sync now`

This is useful because the first registration can happen from the monitored app,
while later edits can still be refreshed from the monitor side.

## Queue And Scheduler Monitoring In The Remote App

The package can expose two optional contributors:

- `queue`
- `scheduler`

To enable them, set the contributor flags in `config/status-probe.php`.

### Scheduler

Add this to the monitored app scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('status-probe:heartbeat scheduler')->everyMinute();
```

### Queue

Enable the `queue` contributor and run a normal Laravel queue worker. The
package updates a queue heartbeat from the worker loop.

If your queue workers and web app instances do not share local cache state, use
a shared cache store for heartbeats.

## Security Recommendations

- keep `STATUS_PROBE_TOKEN` private
- keep `probe_registration_token` private on the monitor side
- do not expose the probe routes without auth unless you explicitly want a
  public health surface
- use HTTPS for monitor-to-app and app-to-monitor communication
- keep `APP_URL` correct so metadata endpoints do not publish bad URLs

## Troubleshooting

### The monitor cannot import metadata

Check:

- the remote `base_url`
- the `metadata_url` override if used
- the remote bearer token
- the remote `APP_URL`
- whether the remote metadata route is reachable from the monitor host

You can verify manually:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://remote-app.example.com/status/metadata
```

### Push registration returns `401`

The monitor-side `probe_registration_token` is missing or the monitored app is
using the wrong `STATUS_MONITOR_TOKEN`.

### Components sync but health checks fail

The monitor may be able to read metadata but not the health endpoint. Check:

- `health_url`
- auth token
- network reachability between the monitor and the monitored app

### You want to monitor a non-Laravel app

Skip the package and create manual HTTP, SSL, DNS, or TCP checks in the monitor
directly.

## Further Reading

- [architecture.md](architecture.md)
- [deployment-and-operations.md](deployment-and-operations.md)
- [../packages/laravel-status-probe/README.md](../packages/laravel-status-probe/README.md)
