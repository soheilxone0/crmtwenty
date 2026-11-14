<?php

namespace Crmtwenty\Persian\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SmtpTestMail extends Mailable
{
    use Queueable;

    public function __construct(
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->fromAddress ? new Address($this->fromAddress, $this->fromName) : null,
            subject: trans('persian::app.emails.test.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'persian::emails.test', with: [
            'sentAt' => core()->formatDate(now(), 'l j F Y، H:i'),
        ]);
    }
}
