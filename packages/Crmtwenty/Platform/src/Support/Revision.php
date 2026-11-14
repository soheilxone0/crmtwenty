<?php

namespace Crmtwenty\Platform\Support;

/**
 * Identifies the deployed code, so database updates run once per deployment.
 */
class Revision
{
    protected static ?string $current = null;

    public static function current(): string
    {
        if (static::$current !== null) {
            return static::$current;
        }

        $file = base_path('REVISION');

        $revision = is_file($file) ? trim((string) file_get_contents($file)) : '';

        return static::$current = substr($revision !== '' ? $revision : 'dev', 0, 64);
    }

    /**
     * Override the revision (tests).
     */
    public static function set(?string $revision): void
    {
        static::$current = $revision;
    }
}
