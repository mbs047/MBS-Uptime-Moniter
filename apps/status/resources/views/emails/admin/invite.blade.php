<x-mail::message>
# Admin access invitation

You have been invited to set up an admin account for {{ config('app.name') }}.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

This invitation will expire in 24 hours.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
