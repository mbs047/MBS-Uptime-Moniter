<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Incident $incident,
        public readonly string $eventType,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->eventType) {
                'created' => 'New incident published',
                'updated' => 'Incident update posted',
                'resolved' => 'Incident resolved',
                default => 'Status incident update',
            },
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscribers.incident',
            with: [
                'incident' => $this->incident,
                'eventType' => $this->eventType,
                'incidentUrl' => route('status.incidents.show', $this->incident->slug),
            ],
        );
    }
}
