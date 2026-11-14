<?php

namespace Crmtwenty\Persian\Mail;

use Crmtwenty\Persian\Support\Jalali;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable;

    /**
     * Minutes the link stays valid.
     */
    public const EXPIRES = 1440;

    public function __construct(public $user) {}

    public static function verificationUrl($user): string
    {
        return URL::temporarySignedRoute(
            'admin.verification.verify',
            now()->addMinutes(self::EXPIRES),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trans('persian::app.emails.verify.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'persian::emails.verify', with: [
            'userName' => $this->user->name,
            'url' => static::verificationUrl($this->user),
            'hours' => app()->getLocale() === 'fa'
                ? Jalali::toPersianDigits((string) intdiv(self::EXPIRES, 60))
                : intdiv(self::EXPIRES, 60),
        ]);
    }
}
