<?php

namespace Crmtwenty\Platform\Support;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Creates / updates the platform's own tables.
 */
class PlatformSchema
{
    public static function path(): string
    {
        return dirname(__DIR__).'/Database/PlatformMigrations';
    }

    public static function migrate(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--database' => Tenancy::CONNECTION,
            '--path' => static::path(),
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Platform migration failed.');
        }
    }
}
