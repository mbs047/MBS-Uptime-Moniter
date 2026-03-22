# Status API

This folder contains repository-level documentation for the public API exposed
by the monitor application in `apps/status`.

Runtime route and controller code lives in:

- `apps/status/routes/api.php`
- `apps/status/app/Http/Controllers/Api`
- `apps/status/app/Http/Controllers/Auth/SubscriberController.php`

## Included Files

- `openapi.yaml`
  OpenAPI 3.1 schema for the public API.
- `examples/summary.json`
- `examples/services.json`
- `examples/incidents.json`
- `examples/subscriber-request.json`
- `examples/subscriber-response.json`

## Route Summary

### Public API Routes

- `GET /api/status/summary`
  Current overall status, generated timestamp, active incident count, affected
  component count, uptime window, and brand metadata.
- `GET /api/status/services`
  Public services, nested public components, current status, 90-day uptime
  summaries, and active incident references.
- `GET /api/status/incidents`
  Published incidents with active incidents first and recent resolved incidents
  after them.
- `POST /api/status/subscribers`
  Idempotent subscriber signup that always returns a neutral success payload.

### Related Public Web Routes

These are public routes but not part of the JSON API:

- `GET /`
- `GET /incidents/{incident:slug}`
- `GET /status/subscribers/confirm/{token}`
- `GET /status/subscribers/unsubscribe/{token}`

## Auth

The public status API is unauthenticated in v1.

The private probe registration route is separate and not described by this
OpenAPI file:

```text
POST /api/integrations/probes/register
```

## Response Notes

### Status Values

Public status fields use:

- `operational`
- `degraded`
- `partial_outage`
- `major_outage`
- `maintenance`

### Incident Severity Values

Incident severity uses:

- `degraded`
- `partial_outage`
- `major_outage`
- `maintenance`

### Incident Status Values

Incident status uses:

- `draft`
- `published`
- `resolved`

Only published incidents are returned by the public incidents endpoint.

## Subscriber Flow

The subscriber flow spans three routes:

1. `POST /api/status/subscribers`
2. `GET /status/subscribers/confirm/{token}`
3. `GET /status/subscribers/unsubscribe/{token}`

The API request creates or refreshes a pending subscriber and sends a
confirmation email if appropriate. Confirmation and unsubscribe are handled
through the tokenized web routes.

## Examples

Use the example payloads in `examples/` to bootstrap clients, mock integrations,
or embedded widgets.

## OpenAPI Usage

The OpenAPI file is intended for:

- external consumers
- generated clients
- internal API review
- embed and widget work

If the application behavior changes, update:

- `openapi.yaml`
- the example payloads
- any long-form docs in `documents/`
