<?php

namespace Crmtwenty\Platform\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Password reset e-mail of a platform administrator.
 */
class PlatformResetPassword extends ResetPassword
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('platform::app.emails.reset.subject', ['app' => config('app.name')]))
            ->view('platform::emails.reset-password', [
                'name' => $notifiable->name,
                'url' => route('platform.password.reset', ['token' => $this->token, 'email' => $notifiable->email]),
                'minutes' => config('auth.passwords.platform_admins.expire', 60),
            ]);
    }
}
