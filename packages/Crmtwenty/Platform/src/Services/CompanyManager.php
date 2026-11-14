<?php

namespace Crmtwenty\Platform\Services;

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Models\Login;
use Crmtwenty\Platform\Support\Logins;
use Crmtwenty\Platform\Support\Revision;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as CrmSeeder;

/**
 * Creates, updates and removes company workspaces.
 */
class CompanyManager
{
    public function __construct(
        protected Tenancy $tenancy,
        protected Logins $logins,
    ) {}

    /**
     * Create a company with its own tables, seed it, and create its manager.
     *
     * @param  array{name: string, manager_name: string, manager_email: string, manager_username: string, manager_password: string, phone?: string|null, notes?: string|null}  $data
     */
    public function create(array $data): Company
    {
        @set_time_limit(0);

        @ignore_user_abort(true);

        $company = Company::query()->create([
            'name' => $data['name'],
            'table_prefix' => 'tmp_'.bin2hex(random_bytes(6)),
            'status' => Company::STATUS_PROVISIONING,
            'manager_name' => $data['manager_name'],
            'manager_email' => Logins::normalize($data['manager_email']),
            'manager_username' => Logins::normalize($data['manager_username']),
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $company->forceFill(['table_prefix' => Tenancy::prefixFor($company->id)])->save();

        try {
            $managerId = $this->tenancy->run($company, function () use ($data) {
                $this->runMigrations();

                app(CrmSeeder::class)->run([
                    'locale' => config('app.locale', 'fa'),
                    'currency' => config('app.currency', 'IRR'),
                ]);

                /**
                 * The seeder creates a demo administrator; it becomes the company manager.
                 */
                $managerId = (int) DB::table('users')->orderBy('id')->value('id');

                DB::table('users')->where('id', $managerId)->update([
                    'name' => $data['manager_name'],
                    'email' => Logins::normalize($data['manager_email']),
                    'username' => Logins::normalize($data['manager_username']),
                    'password' => Hash::make($data['manager_password']),
                    'status' => 1,
                    'view_permission' => 'global',
                    'updated_at' => now(),
                ]);

                return $managerId;
            });

            $company->forceFill([
                'status' => Company::STATUS_ACTIVE,
                'manager_user_id' => $managerId,
                'migrated_revision' => Revision::current(),
            ])->save();

            $this->logins->syncCompany($company);
        } catch (Throwable $e) {
            Log::error('Company provisioning failed', ['company' => $company->id, 'error' => $e->getMessage()]);

            $this->tenancy->dropTables($company);

            Login::query()->where('company_id', $company->id)->delete();

            $company->delete();

            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $company->refresh();
    }

    /**
     * Bring a company's tables up to date with the deployed code (once per deployment).
     */
    public function upgrade(Company $company, bool $force = false): void
    {
        if (! $force && $company->migrated_revision === Revision::current()) {
            return;
        }

        if (! $force && (! config('platform.auto_update', true) || app()->runningUnitTests())) {
            return;
        }

        $lock = Cache::store('file')->lock('platform-upgrade-'.$company->id, 600);

        if (! $lock->get()) {
            return;
        }

        try {
            @set_time_limit(0);

            $this->tenancy->run($company, fn () => $this->runMigrations());

            $company->forceFill(['migrated_revision' => Revision::current()])->save();

            $this->logins->syncCompany($company);
        } finally {
            $lock->release();
        }
    }

    /**
     * Bring the installation's own tables up to date after a deployment, also before the
     * platform is set up (the hosting gives no shell to run `php artisan migrate`).
     */
    public function upgradeBase(): void
    {
        if (! config('platform.auto_update', true) || app()->runningUnitTests()) {
            return;
        }

        if (! is_file(storage_path('installed'))) {
            return;
        }

        $marker = storage_path('framework/crm-revision');

        $current = Revision::current();

        if (is_file($marker) && trim((string) file_get_contents($marker)) === $current) {
            return;
        }

        $lock = Cache::store('file')->lock('platform-upgrade-base', 600);

        if (! $lock->get()) {
            return;
        }

        try {
            @set_time_limit(0);

            $this->runMigrations();

            @file_put_contents($marker, $current);
        } catch (Throwable $e) {
            report($e);
        } finally {
            $lock->release();
        }
    }

    /**
     * Set a new password for the company's manager.
     */
    public function resetManagerPassword(Company $company, string $password): void
    {
        $this->tenancy->run($company, function () use ($company, $password) {
            DB::table('users')->where('id', $company->manager_user_id)->update([
                'password' => Hash::make($password),
                'status' => 1,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Remove a company and all of its data.
     */
    public function delete(Company $company): void
    {
        if ($company->isPrimary()) {
            throw new RuntimeException('The primary company cannot be deleted.');
        }

        $this->tenancy->dropTables($company);

        $this->tenancy->deleteStorage($company);

        Login::query()->where('company_id', $company->id)->delete();

        $company->delete();
    }

    /**
     * Run the CRM migrations on the active connection/prefix.
     */
    protected function runMigrations(): void
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Migration failed.');
        }
    }
}
