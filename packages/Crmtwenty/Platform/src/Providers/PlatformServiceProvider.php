<?php

namespace Crmtwenty\Platform\Providers;

use Crmtwenty\Persian\Support\MailSettings;
use Crmtwenty\Platform\Models\PlatformAdmin;
use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Webkul\User\Models\User;

/**
 * Multi-company platform: company workspaces with isolated data and a platform panel.
 */
class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/platform.php', 'platform');

        Tenancy::registerConnection();

        $this->app->singleton(Tenancy::class);

        $this->app->singleton(Logins::class);

        config([
            'auth.guards.platform' => [
                'driver' => 'session',
                'provider' => 'platform_admins',
            ],
            'auth.providers.platform_admins' => [
                'driver' => 'eloquent',
                'model' => PlatformAdmin::class,
            ],
            'auth.passwords.platform_admins' => [
                'provider' => 'platform_admins',
                'table' => 'platform_password_resets',
                'connection' => Tenancy::CONNECTION,
                'expire' => 60,
                'throttle' => 60,
            ],
        ]);
    }

    public function boot(): void
    {
        /**
         * Capture the base connection settings before anything can change them.
         */
        $this->app->make(Tenancy::class);

        $this->loadMigrationsFrom(dirname(__DIR__).'/Database/Migrations');

        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'platform');

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'platform');

        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/web.php');

        $this->registerMailFallback();

        $this->registerUserIndex();
    }

    /**
     * Companies that have not configured their own SMTP account send through the main
     * installation's account.
     */
    protected function registerMailFallback(): void
    {
        MailSettings::$fallback = function () {
            $tenancy = $this->app->make(Tenancy::class);

            $company = $tenancy->current();

            if (! $company || $company->isPrimary()) {
                return null;
            }

            $rows = DB::connection(Tenancy::CONNECTION)
                ->table('core_config')
                ->where('code', 'like', MailSettings::PREFIX.'%')
                ->pluck('value', 'code')
                ->all();

            $values = [];

            foreach (MailSettings::FIELDS as $field) {
                $values[$field] = $rows[MailSettings::PREFIX.$field] ?? null;
            }

            return filled($values['host']) ? $values : null;
        };
    }

    /**
     * Keep the platform-wide sign-in index in sync with every company's users, and make
     * usernames and e-mail addresses unique across the platform.
     */
    protected function registerUserIndex(): void
    {
        User::saving(function (User $user) {
            $tenancy = $this->app->make(Tenancy::class);

            if (! $tenancy->enabled()) {
                return;
            }

            $logins = $this->app->make(Logins::class);

            $company = $logins->currentCompany();

            $errors = [];

            if ($logins->taken('email', $user->email, $company?->id, $user->getKey())) {
                $errors['email'] = trans('platform::app.validation.email-taken');
            }

            if ($logins->taken('username', $user->getAttribute('username'), $company?->id, $user->getKey())) {
                $errors['username'] = trans('platform::app.validation.username-taken');
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        });

        User::saved(function (User $user) {
            $tenancy = $this->app->make(Tenancy::class);

            if (! $tenancy->enabled()) {
                return;
            }

            $logins = $this->app->make(Logins::class);

            if ($company = $logins->currentCompany()) {
                $logins->put($company, (int) $user->getKey(), (string) $user->email, $user->getAttribute('username'));
            }
        });

        User::deleted(function (User $user) {
            $tenancy = $this->app->make(Tenancy::class);

            if (! $tenancy->enabled()) {
                return;
            }

            $logins = $this->app->make(Logins::class);

            if ($company = $logins->currentCompany()) {
                $logins->forget($company, (int) $user->getKey());
            }
        });
    }
}
