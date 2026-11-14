<?php

namespace Crmtwenty\Persian\Providers;

use Crmtwenty\Persian\Listeners\UserEventSubscriber;
use Crmtwenty\Persian\Support\MailSettings;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * crmtwenty: Persian edition of Krayin CRM.
 *
 *  - Jalali (شمسی) calendar helpers (core()->formatDate() uses them in fa),
 *  - «ایمیل شرکت»: SMTP settings editable from Configuration → Email, with a
 *    test button; they override the .env mail settings at runtime,
 *  - public sign-up, e-mail verification and resend-verification pages.
 */
class PersianServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/core_config.php', 'core_config');

        /**
         * SMTP settings saved in the admin panel are applied right before the
         * mail manager builds its first mailer, so requests that never send
         * mail do not pay for the lookup.
         */
        $this->app->afterResolving('mail.manager', function () {
            MailSettings::apply();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Database/Migrations');

        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'persian');

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'persian');

        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/web.php');

        Event::subscribe(UserEventSubscriber::class);

        /**
         * Relative dates ("۳ ساعت پیش") follow the panel language.
         */
        Carbon::setLocale($this->app->getLocale());

        Event::listen(LocaleUpdated::class, fn (LocaleUpdated $event) => Carbon::setLocale($event->locale));

        /**
         * "Create an account" / "Resend verification e-mail" links under the
         * login form.
         */
        Event::listen('admin.sessions.login.form_controls.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('persian::auth.login-links');
        });
    }
}
