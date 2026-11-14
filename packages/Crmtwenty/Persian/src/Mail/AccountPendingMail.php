<?php

namespace Crmtwenty\Persian\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells administrators that a self-registered account awaits activation.
 */
class AccountPendingMail extends Mailable
{
    use Queueable;

    public function __construct(public $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trans('persian::app.emails.pending.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'persian::emails.pending', with: [
            'userName' => $this->user->name,
            'userEmail' => $this->user->email,
            'url' => route('admin.settings.users.index'),
        ]);
    }
}
