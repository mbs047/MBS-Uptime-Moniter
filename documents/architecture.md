# Architecture

This document describes the core domain model and runtime behavior of the
monitor application.

## High-Level Shape

The system has three faces:

- a public status site
- a private Filament admin panel
- a monitoring engine that runs checks, rolls up status, and stores history

It also supports package-enabled remote Laravel apps through the
`laravel-status-probe` package.

## Domain Model

### Public Monitoring Model

- `Service`
  Public grouping such as API, Auth, Billing, or Email.
- `Component`
  Public item inside a service with display name, description, sort order,
  public visibility, and cached derived status.
- `Check`
  Automated monitor for a component. Stores type, interval, timeout,
  thresholds, enablement, and driver config.
- `CheckRun`
  Append-only execution result with outcome, status code, latency, errors, and
  timestamps.
- `ComponentDailyUptime`
  Daily aggregate used for uptime percentages and 90-day bars.

### Incident and Notification Model

- `Incident`
  Manual incident record with severity, publication state, timing, and affected
  services and components.
- `IncidentUpdate`
  Timeline posts attached to an incident.
- `Subscriber`
  Email subscriber with verification and unsubscribe timestamps.

### Admin and Settings Model

- `Admin`
  Internal admin user for Filament.
- `AdminInvite`
  Invite flow for creating admins.
- `PlatformSetting`
  Single record for brand, mail, retention, default thresholds, SEO, and probe
  registration token.

### Remote Integration Model

- `RemoteIntegration`
  Represents a package-enabled remote Laravel application.
- `remote_component_key`
  Used on local `components` and `checks` so sync can upsert without creating
  duplicates.

## Supported Check Drivers

The monitor currently supports:

- `http`
- `ssl`
- `dns`
- `tcp`

### HTTP Checks

HTTP checks support:

- method
- URL
- headers
- optional auth
- optional JSON body
- expected status codes
- max latency
- text contains assertions
- JSON-path style assertions
- `status_json_path` for package-enabled shared health payloads

### SSL Checks

SSL checks evaluate:

- handshake reachability
- certificate expiry

### DNS Checks

DNS checks evaluate:

- record resolution
- optional expected value presence

### TCP Checks

TCP checks evaluate:

- host and port reachability

## Automated Status Mapping

Automated health is derived from check outcomes.

- passing checks map to `operational`
- soft failures map to `degraded`
- hard failures become outage-class

Component derivation rules:

- if hard failures exist and at least one enabled check still passes, the
  component becomes `partial_outage`
- if all enabled checks are hard-failing, the component becomes `major_outage`
- `maintenance` is never created by automated checks

## Incident Overlay and Precedence

Published incidents overlay automated health using this precedence:

```text
maintenance > major_outage > partial_outage > degraded > operational
```

Service-level incidents cascade to all public components within that service for
public rendering and API payloads.

## Incident Workflow

Incidents are manual by design.

State transitions:

- `draft`
- `published`
- `resolved`

Important rule:

- automated failures do not auto-publish incidents

Instead, admins use failing checks and dashboard prompts to decide whether to
publish an incident.

## Notification Rules

Subscribers only receive email for:

- published incident created events
- published incident update events
- published incident resolved events

Subscribers do not receive email for:

- draft incidents
- automated failures without a published incident

## Scheduling and Execution

The scheduler dispatches due checks every minute.

Runtime flow:

1. `status:dispatch-due-checks` finds enabled checks whose `next_run_at` is due.
2. A queued job executes each check.
3. A `CheckRun` row is written.
4. Consecutive failure and recovery counters are updated.
5. The latest check severity is recalculated.
6. Component and service status rollups are recalculated.
7. Uptime aggregation jobs later refresh daily summaries.

Nightly maintenance tasks:

- refresh current and previous daily uptime windows
- prune raw `check_runs` older than retention

## Uptime Aggregation

`ComponentDailyUptime` powers:

- uptime percentages
- 90-day bars
- service-level public history summaries

The model uses expected slots derived from each check's interval.

Rules:

- maintenance periods are excluded from the denominator
- no-data periods are excluded from the denominator
- service uptime is the mean of visible component uptime values

## Admin Panel

The Filament panel includes resources and pages for:

- dashboard
- services
- components
- checks
- incidents
- subscribers
- platform settings
- admins
- admin invites
- remote integrations

Notable workflows:

- `Run now` check action for manual execution
- remote integration sync from metadata
- incident publish and resolve flow
- subscriber visibility

## Public Surface

The public side includes:

- overall status banner
- active incidents and maintenance
- grouped service and component health
- 90-day uptime bars
- incident history
- incident detail pages

The public API exposes:

- summary
- services
- incidents
- subscriber creation

## Probe Package Integration

The `mbs047/laravel-status-probe` package lets another Laravel app describe
itself to the monitor.

The package exposes:

- an authenticated health payload
- an authenticated metadata payload

The monitor can:

- pull metadata from the remote app
- accept pushed registration payloads
- create one service and many components from that metadata
- create one shared-health HTTP check per component

## Security Notes

- admin bootstrap is protected by `APP_SETUP_TOKEN`
- remote probe push registration is protected by a bearer token stored in
  `Platform Settings`
- remote bearer secrets are stored encrypted on `RemoteIntegration`
- check secret config is stored separately from non-secret config

## Further Reading

- [getting-started.md](getting-started.md)
- [monitoring-laravel-apps.md](monitoring-laravel-apps.md)
- [deployment-and-operations.md](deployment-and-operations.md)
- [../packages/laravel-status-probe/README.md](../packages/laravel-status-probe/README.md)
