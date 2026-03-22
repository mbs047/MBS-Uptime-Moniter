<?php

namespace App\Services\Status;

use App\Mail\IncidentNotificationMail;
use App\Models\Incident;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

class IncidentNotifier
{
    public function send(Incident $incident, string $eventType): void
    {
        $incident->loadMissing([
            'services:id,name,slug',
            'components:id,display_name',
            'updates' => fn ($query) => $query->whereNotNull('published_at')->latest('published_at')->limit(1),
        ]);

        Subscriber::query()
            ->activeRecipients()
            ->each(fn (Subscriber $subscriber) => Mail::to($subscriber->email)->queue(
                new IncidentNotificationMail($incident, $eventType),
            ));
    }
}
