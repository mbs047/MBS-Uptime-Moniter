<?php

namespace Tests\Feature;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Mail\IncidentNotificationMail;
use App\Models\Incident;
use App\Models\Subscriber;
use App\Services\Status\IncidentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IncidentNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_active_subscribers_receive_incident_notifications(): void
    {
        Mail::fake();

        $incident = Incident::query()->create([
            'title' => 'API outage',
            'slug' => 'api-outage',
            'status' => IncidentStatus::Published,
            'severity' => IncidentSeverity::MajorOutage,
            'published_at' => now(),
        ]);

        $activeSubscriber = Subscriber::query()->create([
            'email' => 'active@example.com',
            'unsubscribe_token' => 'unsubscribe-active',
            'verified_at' => now(),
        ]);

        Subscriber::query()->create([
            'email' => 'pending@example.com',
            'unsubscribe_token' => 'unsubscribe-pending',
        ]);

        Subscriber::query()->create([
            'email' => 'former@example.com',
            'unsubscribe_token' => 'unsubscribe-former',
            'verified_at' => now(),
            'unsubscribed_at' => now(),
        ]);

        app(IncidentNotifier::class)->send($incident, 'created');

        Mail::assertQueued(IncidentNotificationMail::class, 1);
        Mail::assertQueued(IncidentNotificationMail::class, function (IncidentNotificationMail $mail) use ($activeSubscriber): bool {
            return $mail->hasTo($activeSubscriber->email) && $mail->eventType === 'created';
        });
    }
}
