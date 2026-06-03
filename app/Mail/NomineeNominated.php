<?php

namespace App\Mail;

use App\Models\Nomination;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NomineeNominated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Nomination $nomination)
    {
        $this->nomination->loadMissing('nominee', 'category', 'nominatedBy');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been nominated for Extraordinary African'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nominee-nominated'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
