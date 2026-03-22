# Custom Contributors

Custom contributors let you expose app-specific health signals beyond the
built-in package probes.

Examples:

- search cluster health
- object storage access
- Redis health
- upstream API dependencies
- internal domain-specific health checks

## Contract

Implement:

```php
Mbs047\LaravelStatusProbe\Contracts\ProbeContributor
```

Methods:

- `key()`
  Stable machine-readable component key.
- `label()`
  Human-readable component name.
- `description()`
  Optional public description.
- `defaultCheckConfig()`
  Default settings surfaced to the monitor metadata payload.
- `resolve()`
  Returns a `ProbeResult`.

## Minimal Example

```php
use Mbs047\LaravelStatusProbe\Contracts\ProbeContributor;
use Mbs047\LaravelStatusProbe\Enums\ProbeStatus;
use Mbs047\LaravelStatusProbe\ProbeResult;

class SearchProbeContributor implements ProbeContributor
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
        return new ProbeResult(
            ProbeStatus::Operational,
            'Search is healthy.',
        );
    }
}
```

## Registering The Contributor

Bind it in a service provider and tag it:

```php
$this->app->singleton(SearchProbeContributor::class);
$this->app->tag(SearchProbeContributor::class, 'status-probe.contributors');
```

Once tagged, it appears automatically in:

- the health payload
- the metadata payload

## Status Values

Use the package enum values:

- `operational`
- `degraded`
- `partial_outage`
- `major_outage`

Pick the most accurate value for the dependency you are checking.

## Exception Handling

If a contributor throws an exception during resolution:

- the package catches it
- the contributor is reported as `major_outage`
- the endpoint still returns a valid health payload

This keeps one failing contributor from breaking the entire probe surface.

## Default Check Config

`defaultCheckConfig()` lets you surface monitor-friendly defaults in the
metadata payload. For example, you can return overrides for interval or timeout
values that a monitor may use when it creates the HTTP check for that
component.

## Design Tips

- keep keys stable once published
- return short, human-readable summaries
- keep details structured and JSON-friendly
- avoid long-running or expensive checks inside `resolve()`
- prefer deterministic probes over heuristics

## Related Docs

- [installation.md](installation.md)
- [connecting-to-monitor.md](connecting-to-monitor.md)
