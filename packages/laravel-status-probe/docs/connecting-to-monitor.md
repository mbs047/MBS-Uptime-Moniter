# Connecting To A Monitor

This guide explains how a Laravel app using `mbs047/laravel-status-probe`
connects to a status monitor.

The package supports two practical patterns:

- pull
- push

## Pull Model

In the pull model, the status monitor calls the monitored app.

Flow:

1. the monitored app exposes `status/health` and `status/metadata`
2. the monitor calls the metadata endpoint
3. the monitor creates or updates services, components, and HTTP checks
4. the monitor calls the health endpoint on schedule

### What You Need On The Monitored App

- correct `APP_URL`
- a configured `STATUS_PROBE_TOKEN`
- reachable `health` and `metadata` routes

### What The Monitor Needs

- the monitored app base URL
- the bearer token
- network access to the monitored app

## Push Model

In the push model, the monitored app registers itself with the monitor.

Flow:

1. the monitor exposes a private registration endpoint
2. the monitor stores a registration bearer token
3. the monitored app posts its metadata payload to the monitor
4. the monitor upserts the linked service, components, and checks

The package command used for this is:

```bash
php artisan status-probe:register
```

The package sends the metadata payload to:

```text
POST {STATUS_MONITOR_URL}/api/integrations/probes/register
```

## Package-Side Configuration For Push

Set:

```dotenv
STATUS_MONITOR_URL=https://status.example.com
STATUS_MONITOR_TOKEN=your-monitor-token
```

Then run:

```bash
php artisan status-probe:register
```

## Monitor-Side Requirements For Push

Your monitor needs:

- a valid `probe_registration_token`
- a working private route at `/api/integrations/probes/register`
- logic that accepts the metadata payload and creates or updates local monitor
  records

## Hybrid Usage

Many teams use both:

- initial registration through push
- later refreshes through pull

That keeps setup simple while still allowing the monitor to re-sync from the
remote metadata endpoint whenever needed.

## What The Monitor Learns From Metadata

The metadata payload includes:

- stable `app_id`
- service name, slug, and description
- `base_url`
- `health_url`
- `metadata_url`
- auth mode
- component list
- `status_json_path` for each component
- suggested HTTP check settings

This lets the monitor create:

- one local service
- one local component per contributor
- one HTTP check per component against the shared health URL

## Verifying The Connection

### Verify Health

```bash
curl \
  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN" \
  https://your-app.example.com/status/health
```

### Verify Metadata

```bash
curl \
  -H "Authorization: Bearer YOUR_STATUS_PROBE_TOKEN" \
  https://your-app.example.com/status/metadata
```

### Verify Push Registration

After `php artisan status-probe:register`, confirm the monitor shows the new or
updated remote integration, linked service, and generated component checks.

## Troubleshooting

### The monitor cannot pull metadata

Check:

- `APP_URL`
- `STATUS_PROBE_TOKEN`
- remote route reachability
- HTTPS and DNS correctness

### Push registration returns `401`

The monitor-side registration token is missing or does not match
`STATUS_MONITOR_TOKEN`.

### The monitor imports the app but checks stay unhealthy

The metadata endpoint may work while the health endpoint does not. Verify:

- `health_url`
- bearer token
- network reachability from the monitor host

## Related Docs

- [installation.md](installation.md)
- [custom-contributors.md](custom-contributors.md)
