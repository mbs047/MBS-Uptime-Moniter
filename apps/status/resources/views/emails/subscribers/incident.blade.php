<x-mail::message>
# {{ $incident->title }}

{{ $incident->summary ?: 'A status incident has changed.' }}

Severity: **{{ str($incident->severity->value)->replace('_', ' ')->title() }}**

Status: **{{ str($incident->status->value)->title() }}**

<x-mail::button :url="$incidentUrl">
View incident timeline
</x-mail::button>

You can unsubscribe at any time from future updates using the link in your status site.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
