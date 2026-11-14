<?php

namespace Webkul\Installer\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\Installer\Console\Commands\Installer as InstallerCommand;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\Installer\Http\Middleware\Locale;

class InstallerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     */
    protected bool $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot(Router $router): void
    {
        $router->middlewareGroup('install', [CanInstall::class]);

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'installer');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'installer');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'installer');

        $router->aliasMiddleware('installer_locale', Locale::class);

        /**
         * crmtwenty: the upstream "installed" listener reported the domain and the administrator's
         * name and e-mail to a third-party server, and the image-cache route fetched a remote logo
         * (sending the same details) on every page view. Both are removed: nothing leaves this
         * installation.
         */
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'installer');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([InstallerCommand::class]);
    }
}
