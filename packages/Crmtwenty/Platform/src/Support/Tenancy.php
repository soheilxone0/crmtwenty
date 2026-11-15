<?php

namespace Crmtwenty\Platform\Support;

use Closure;
use Crmtwenty\Persian\Support\MailSettings;
use Crmtwenty\Platform\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Multi-company isolation.
 *
 * Every company gets its own complete set of CRM tables inside the one database the hosting
 * provides, told apart by a table prefix (`c12_leads`, `c12_users`, …, on top of the
 * installation's own prefix). Activating a company points the default database connection at
 * its prefix — and its uploads and cache at its own space — so the whole CRM, raw queries and
 * data grids included, only ever sees that company's data. Platform tables (`platform_*`) are
 * reached through the separate `platform` connection, which keeps the base prefix.
 *
 * Until a platform administrator exists the application behaves as a plain single-company
 * installation, which keeps the installer, the test-suite and existing installs working.
 */
class Tenancy
{
    public const CONNECTION = 'platform';

    /**
     * Session key holding the signed-in company.
     */
    public const SESSION_KEY = 'platform_company_id';

    protected ?Company $company = null;

    protected ?bool $enabled = null;

    /**
     * Settings of the default connection before any company was activated.
     */
    protected array $base;

    public function __construct()
    {
        $name = config('database.default');

        $this->base = [
            'connection' => $name,
            'prefix' => (string) config("database.connections.$name.prefix", ''),
            'cache_prefix' => (string) config('cache.prefix', ''),
            'disks' => [
                'local' => config('filesystems.disks.local'),
                'public' => config('filesystems.disks.public'),
            ],
        ];
    }

    /**
     * Register the `platform` connection: same database, base prefix.
     */
    public static function registerConnection(): void
    {
        $name = config('database.default');

        $config = config("database.connections.$name");

        if (is_array($config) && ! config('database.connections.'.self::CONNECTION)) {
            config(['database.connections.'.self::CONNECTION => $config]);
        }
    }

    /**
     * Whether the platform has been set up (a platform administrator exists).
     */
    public function enabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        try {
            $connection = DB::connection(self::CONNECTION);

            if (! $connection->getSchemaBuilder()->hasTable('platform_admins')) {
                return $this->enabled = false;
            }

            return $this->enabled = $connection->table('platform_admins')->exists();
        } catch (Throwable) {
            return $this->enabled = false;
        }
    }

    /**
     * Whether the platform tables exist.
     */
    public function installed(): bool
    {
        try {
            return DB::connection(self::CONNECTION)->getSchemaBuilder()->hasTable('platform_admins');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Forget cached state (after the platform is set up, and between tests).
     */
    public function flush(): void
    {
        $this->enabled = null;
    }

    public function current(): ?Company
    {
        return $this->company;
    }

    /**
     * Where a CRM user should go to manage companies, or null when they may not.
     *
     * Only full administrators of the primary company (the original installation) get the
     * shortcut; managers of the other companies never see the platform.
     */
    public function managementUrl(mixed $user): ?string
    {
        if (! $user || ($user->role->permission_type ?? null) !== 'all') {
            return null;
        }

        if (! $this->enabled()) {
            return route('platform.setup.create');
        }

        if ($this->company && ! $this->company->isPrimary()) {
            return null;
        }

        return route('platform.companies.create');
    }

    /**
     * The base table prefix of the installation.
     */
    public function basePrefix(): string
    {
        return $this->base['prefix'];
    }

    /**
     * The full table prefix of a company on the default connection.
     */
    public function fullPrefix(Company $company): string
    {
        return $this->base['prefix'].$company->table_prefix;
    }

    /**
     * Point the application at the given company's data.
     */
    public function activate(Company $company): void
    {
        $this->company = $company;

        $this->setPrefix($this->fullPrefix($company));

        $this->configureStorage($company->isPrimary() ? null : 'companies/'.$company->id);

        config(['cache.prefix' => $company->isPrimary()
            ? $this->base['cache_prefix']
            : $this->base['cache_prefix'].'_c'.$company->id,
        ]);

        $this->refreshServices();
    }

    /**
     * Return to the installation's own (unprefixed) data.
     */
    public function deactivate(): void
    {
        $this->company = null;

        $this->setPrefix($this->base['prefix']);

        $this->configureStorage(null);

        config(['cache.prefix' => $this->base['cache_prefix']]);

        $this->refreshServices();
    }

    /**
     * Run the callback with the given company active, restoring the previous state afterwards.
     */
    public function run(Company $company, Closure $callback): mixed
    {
        $previous = $this->company;

        $this->activate($company);

        try {
            return $callback($company);
        } finally {
            $previous ? $this->activate($previous) : $this->deactivate();
        }
    }

    /**
     * The table prefix (relative to the base prefix) of a company id.
     */
    public static function prefixFor(int $id): string
    {
        return 'c'.$id.'_';
    }

    /**
     * Count rows of one of a company's tables without activating it.
     */
    public function countIn(Company $company, string $table): int
    {
        try {
            return DB::connection(self::CONNECTION)->table($company->table_prefix.$table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Raw names of every table of a (non-primary) company.
     */
    public function tablesOf(Company $company): array
    {
        if (! preg_match('/^c\d+_$/', $company->table_prefix)) {
            return [];
        }

        $prefix = $this->fullPrefix($company);

        $like = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $prefix).'%';

        $rows = DB::connection(self::CONNECTION)->select(
            'select table_name as name from information_schema.tables where table_schema = database() and table_name like ?',
            [$like]
        );

        return collect($rows)
            ->map(fn ($row) => (string) array_values((array) $row)[0])
            ->filter(fn ($table) => str_starts_with($table, $prefix))
            ->values()
            ->all();
    }

    /**
     * Drop every table of a (non-primary) company.
     */
    public function dropTables(Company $company): void
    {
        $tables = $this->tablesOf($company);

        if (! $tables) {
            return;
        }

        $pdo = DB::connection(self::CONNECTION)->getPdo();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `'.str_replace('`', '', $table).'`');
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Delete a company's upload folders.
     */
    public function deleteStorage(Company $company): void
    {
        if ($company->isPrimary()) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            $root = $this->base['disks'][$disk]['root'] ?? null;

            if ($root) {
                File::deleteDirectory(rtrim($root, '/').'/companies/'.$company->id);
            }
        }
    }

    /**
     * Find the company that owns a public web form (embedded forms carry no session).
     */
    public function companyForWebForm(string $formId): ?Company
    {
        foreach (Company::query()->where('status', Company::STATUS_ACTIVE)->get() as $company) {
            try {
                $exists = DB::connection(self::CONNECTION)
                    ->table($company->table_prefix.'web_forms')
                    ->where('form_id', $formId)
                    ->exists();
            } catch (Throwable) {
                continue;
            }

            if ($exists) {
                return $company;
            }
        }

        return null;
    }

    /**
     * Change the table prefix of the default connection, also on an already open connection.
     */
    protected function setPrefix(string $prefix): void
    {
        $name = $this->base['connection'];

        config(["database.connections.$name.prefix" => $prefix]);

        if (array_key_exists($name, DB::getConnections())) {
            DB::connection($name)->setTablePrefix($prefix);
        }
    }

    /**
     * Give each company its own upload folders.
     */
    protected function configureStorage(?string $folder): void
    {
        $local = $this->base['disks']['local'];

        $public = $this->base['disks']['public'];

        if ($folder) {
            if (is_array($local) && isset($local['root'])) {
                $local['root'] = rtrim($local['root'], '/').'/'.$folder;

                File::ensureDirectoryExists($local['root']);
            }

            if (is_array($public) && isset($public['root'])) {
                $public['root'] = rtrim($public['root'], '/').'/'.$folder;

                if (isset($public['url'])) {
                    $public['url'] = rtrim((string) $public['url'], '/').'/'.$folder;
                }

                File::ensureDirectoryExists($public['root']);
            }
        }

        config([
            'filesystems.disks.local' => $local,
            'filesystems.disks.public' => $public,
        ]);

        if (app()->resolved('filesystem')) {
            app('filesystem')->forgetDisk(['local', 'public']);
        }
    }

    /**
     * Rebuild services that captured company specific state.
     */
    protected function refreshServices(): void
    {
        if (app()->resolved('cache')) {
            app('cache')->forgetDriver(config('cache.default'));
        }

        MailSettings::flush();

        if (app()->resolved('mail.manager')) {
            MailSettings::apply();

            app('mail.manager')->forgetMailers();
        }
    }
}
