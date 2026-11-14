<?php

namespace Crmtwenty\Platform\Support;

use Throwable;

/**
 * Removes files left behind by earlier versions.
 *
 * Updates are installed by extracting a ZIP over the existing folder, which adds and replaces
 * files but never deletes them. This runs once per deployment and tidies up what the current
 * version no longer ships.
 */
class Obsolete
{
    /**
     * Paths relative to the installation root (glob patterns allowed).
     */
    public const PATHS = [
        'config/k*-vite.php',
        'packages/Webkul/Admin/src/Config/support.php',
        'packages/Webkul/Admin/src/Http/Controllers/HelpController.php',
        'packages/Webkul/Admin/src/Resources/assets/fonts/icomoon.woff',
        'packages/Webkul/Admin/src/Resources/views/help/card.blade.php',
        'packages/Webkul/Admin/src/Resources/views/help/index.blade.php',
        'packages/Webkul/Admin/src/Routes/Admin/help-routes.php',
        'packages/Webkul/Installer/src/Http/Controllers/ImageCacheController.php',
        'packages/Webkul/Installer/src/Listeners/Installer.php',
        'packages/Webkul/Installer/src/Resources/assets/images/k*-logo.svg',
        'public/admin/build/assets/icomoon-*',
        'public/installer/build/assets/icomoon-*',
        'public/installer/build/assets/k*-logo-*.svg',
        'public/webform/build/assets/icomoon-*',
    ];

    /**
     * Vite build folders: hashed files the current manifest no longer references are removed.
     */
    public const BUILDS = [
        'public/admin/build',
        'public/installer/build',
        'public/webform/build',
    ];

    public static function clean(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $marker = storage_path('framework/crm-cleanup');

        $current = Revision::current();

        if ($current === 'dev' || (is_file($marker) && trim((string) file_get_contents($marker)) === $current)) {
            return;
        }

        @file_put_contents($marker, $current);

        try {
            foreach (self::PATHS as $pattern) {
                foreach (glob(base_path($pattern)) ?: [] as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }

            foreach (self::BUILDS as $build) {
                self::cleanBuild(base_path($build));
            }

            @rmdir(base_path('packages/Webkul/Admin/src/Resources/views/help'));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Remove stale `app-<hash>.js|css` bundles that the manifest no longer points to.
     */
    protected static function cleanBuild(string $dir): void
    {
        $manifest = $dir.'/manifest.json';

        if (! is_file($manifest)) {
            return;
        }

        $json = (string) file_get_contents($manifest);

        if (json_decode($json, true) === null) {
            return;
        }

        foreach (['js', 'css'] as $extension) {
            foreach (glob($dir.'/assets/app-*.'.$extension) ?: [] as $file) {
                if (! str_contains($json, 'assets/'.basename($file))) {
                    @unlink($file);
                }
            }
        }
    }
}
