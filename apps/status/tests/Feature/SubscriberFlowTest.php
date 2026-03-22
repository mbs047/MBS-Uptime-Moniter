<?php

namespace Tests\Feature;

use App\Mail\SubscriberConfirmationMail;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\RefreshDedicatedDatabase;
use Tests\TestCase;

class SubscriberFlowTest extends TestCase
{
    use RefreshDedicatedDatabase;

    public function test_subscribers_can_confirm_and_unsubscribe(): void
    {
        Mail::fake();

        $this->postJson('/api/status/subscribers', [
            'email' => 'ops@example.com',
        ])->assertOk();

        $subscriber = Subscriber::query()->firstOrFail();

        Mail::assertQueued(SubscriberConfirmationMail::class);

        $this->get("/status/subscribers/confirm/{$subscriber->verification_token}")
            ->assertRedirect('/');

        $subscriber->refresh();

        $this->assertNotNull($subscriber->verified_at);
        $this->assertNull($subscriber->verification_token);

        $this->get("/status/subscribers/unsubscribe/{$subscriber->unsubscribe_token}")
            ->assertRedirect('/');

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }
}
