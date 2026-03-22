<x-mail::message>
# Confirm your subscription

Confirm this email address to receive status incident notifications.

<x-mail::button :url="$confirmUrl">
Confirm subscription
</x-mail::button>

If you did not request this, you can ignore the email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
