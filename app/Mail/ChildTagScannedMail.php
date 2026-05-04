<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChildTagScannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $childName,
        public ?string $lat,
        public ?string $lng,
        public string $scannedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Child Tag Scanned - ' . $this->childName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.child_tag_scan',
        );
    }
}
