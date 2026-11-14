<?php

namespace Crmtwenty\Persian\Listeners;

use Crmtwenty\Persian\Support\Registration;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserEventSubscriber
{
    /**
     * A self-registered user who has not confirmed the e-mail address yet is
     * signed straight back out, with a link to resend the verification e-mail.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        $user = $event->user;

        if (! $user instanceof Model || ! $user->getAttribute('self_registered') || $user->getAttribute('email_verified_at')) {
            return;
        }

        if (! Registration::requiresVerification()) {
            return;
        }

        auth()->guard('user')->logout();

        throw new HttpResponseException(
            redirect()->route('admin.session.create')
                ->withInput(['email' => $user->getAttribute('email')])
                ->with('warning', trans('persian::app.verification.not-verified'))
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
        ];
    }
}
