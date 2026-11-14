<?php

use Crmtwenty\Platform\Models\Company;
use Crmtwenty\Platform\Models\Login;
use Crmtwenty\Platform\Models\PlatformAdmin;
use Crmtwenty\Platform\Services\CompanyManager;
use Crmtwenty\Platform\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Webkul\Admin\Notifications\User\UserResetPassword;

/**
 * crmtwenty: the multi-company platform.
 *
 * These tests create and drop real tables (DDL commits implicitly in MySQL), so they run in
 * their own, sequential test suite and clean up after themselves.
 */
function platformCleanup(): void
{
    $tenancy = app(Tenancy::class);

    $tenancy->deactivate();

    $connection = DB::connection(Tenancy::CONNECTION);

    if ($connection->getSchemaBuilder()->hasTable('platform_companies')) {
        foreach (Company::query()->get() as $company) {
            $tenancy->dropTables($company);

            $tenancy->deleteStorage($company);
        }
    }

    foreach (['platform_settings', 'platform_logins', 'platform_companies', 'platform_password_resets', 'platform_admins'] as $table) {
        $connection->getSchemaBuilder()->dropIfExists($table);
    }

    $connection->table('migrations')->where('migration', 'like', '%create_platform_tables')->delete();

    $tenancy->flush();
}

function platformSetup(): PlatformAdmin
{
    test()->post(route('platform.setup.store'), [
        'crm_email' => 'admin@example.com',
        'crm_password' => 'admin123',
        'company_name' => 'Main company',
        'name' => 'Platform Owner',
        'username' => 'owner',
        'email' => 'owner@example.com',
        'password' => 'owner-pass-123',
        'password_confirmation' => 'owner-pass-123',
    ])->assertRedirect(route('platform.dashboard'));

    app(Tenancy::class)->flush();

    return PlatformAdmin::query()->where('username', 'owner')->firstOrFail();
}

function platformCompany(string $suffix = 'a'): Company
{
    return app(CompanyManager::class)->create([
        'name' => 'Company '.strtoupper($suffix),
        'manager_name' => 'Manager '.strtoupper($suffix),
        'manager_username' => 'manager-'.$suffix,
        'manager_email' => 'manager-'.$suffix.'@example.com',
        'manager_password' => 'manager-pass-123',
    ]);
}

beforeEach(function () {
    URL::forceRootUrl('http://localhost');

    platformCleanup();

    /**
     * The default administrator seeded by the installer verifies the setup.
     */
    $admin = DB::table('users')->where('email', 'admin@example.com')->first();

    if ($admin) {
        DB::table('users')->where('id', $admin->id)->update(['password' => Hash::make('admin123'), 'status' => 1]);
    }
});

afterEach(function () {
    platformCleanup();
});

it('stays a plain single-company installation until it is set up', function () {
    expect(app(Tenancy::class)->enabled())->toBeFalse();

    test()->get(route('platform.session.create'))->assertRedirect(route('platform.setup.create'));

    test()->get(route('platform.setup.create'))->assertOk();
});

it('only lets a CRM administrator set the platform up', function () {
    test()->post(route('platform.setup.store'), [
        'crm_email' => 'admin@example.com',
        'crm_password' => 'wrong-password',
        'company_name' => 'Main company',
        'name' => 'Intruder',
        'username' => 'intruder',
        'email' => 'intruder@example.com',
        'password' => 'intruder-123',
        'password_confirmation' => 'intruder-123',
    ])->assertSessionHasErrors('crm_email');

    expect(app(Tenancy::class)->installed() && PlatformAdmin::query()->exists())->toBeFalse();
});

it('sets the platform up with the existing data as the primary company', function () {
    platformSetup();

    $primary = Company::query()->where('table_prefix', '')->first();

    expect($primary)->not->toBeNull()
        ->and($primary->status)->toBe(Company::STATUS_ACTIVE)
        ->and(Login::query()->where('company_id', $primary->id)->where('email', 'admin@example.com')->exists())->toBeTrue();

    test()->get(route('platform.dashboard'))->assertOk()->assertSee('Main company');

    test()->get(route('platform.setup.create'))->assertRedirect(route('platform.session.create'));
});

it('signs platform administrators in with their username', function () {
    platformSetup();

    auth()->guard('platform')->logout();

    test()->post(route('platform.session.store'), ['login' => 'owner', 'password' => 'nope'])
        ->assertSessionHasErrors('login');

    test()->post(route('platform.session.store'), ['login' => 'owner', 'password' => 'owner-pass-123'])
        ->assertRedirect(route('platform.dashboard'));

    expect(auth()->guard('platform')->check())->toBeTrue();
});

it('creates a company with its own isolated tables and manager', function () {
    platformSetup();

    $company = platformCompany('a');

    $prefix = app(Tenancy::class)->fullPrefix($company);

    expect($company->status)->toBe(Company::STATUS_ACTIVE)
        ->and($company->table_prefix)->toBe('c'.$company->id.'_')
        ->and(Schema::connection(Tenancy::CONNECTION)->hasTable($company->table_prefix.'leads'))->toBeTrue()
        ->and(Login::query()->where('username', 'manager-a')->value('company_id'))->toBe($company->id);

    $manager = DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'users')->first();

    expect($manager->email)->toBe('manager-a@example.com')
        ->and($manager->username)->toBe('manager-a')
        ->and(Hash::check('manager-pass-123', $manager->password))->toBeTrue()
        ->and(DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'users')->count())->toBe(1)
        ->and(str_starts_with($prefix, app(Tenancy::class)->basePrefix()))->toBeTrue();

    /**
     * Data written while the company is active stays inside it.
     */
    app(Tenancy::class)->run($company, function () use ($manager) {
        DB::table('tags')->insert(['name' => 'only-in-company-a', 'color' => '#000', 'user_id' => $manager->id, 'created_at' => now(), 'updated_at' => now()]);
    });

    expect(DB::table('tags')->where('name', 'only-in-company-a')->exists())->toBeFalse()
        ->and(DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'tags')->where('name', 'only-in-company-a')->exists())->toBeTrue();
});

it('signs company users in to their own company by username or email', function () {
    platformSetup();

    $company = platformCompany('a');

    test()->post(route('admin.session.store'), ['email' => 'manager-a', 'password' => 'manager-pass-123'])
        ->assertRedirect();

    expect(auth()->guard('user')->check())->toBeTrue()
        ->and(auth()->guard('user')->user()->email)->toBe('manager-a@example.com')
        ->and(session(Tenancy::SESSION_KEY))->toBe($company->id);

    test()->get(route('admin.dashboard.index'))->assertOk();

    expect(app(Tenancy::class)->current()?->id)->toBe($company->id);

    test()->delete(route('admin.session.destroy'));

    test()->post(route('admin.session.store'), ['email' => 'manager-a@example.com', 'password' => 'manager-pass-123'])
        ->assertRedirect();

    expect(auth()->guard('user')->user()?->username)->toBe('manager-a');
});

it('keeps usernames and e-mail addresses unique across companies', function () {
    platformSetup();

    platformCompany('a');

    test()->post(route('platform.companies.store'), [
        'name' => 'Company B',
        'manager_name' => 'Copycat',
        'manager_username' => 'manager-a',
        'manager_email' => 'admin@example.com',
        'manager_password' => 'manager-pass-123',
        'manager_password_confirmation' => 'manager-pass-123',
    ])->assertSessionHasErrors(['manager_username', 'manager_email']);

    expect(Company::query()->count())->toBe(2);
});

it('blocks the users of a suspended company', function () {
    platformSetup();

    $company = platformCompany('a');

    $company->update(['status' => Company::STATUS_SUSPENDED]);

    test()->post(route('admin.session.store'), ['email' => 'manager-a', 'password' => 'manager-pass-123'])
        ->assertRedirect(route('admin.session.create'))
        ->assertSessionHas('error');

    expect(auth()->guard('user')->check())->toBeFalse();
});

it('sends the password reset of a company user from that company', function () {
    Notification::fake();

    platformSetup();

    $company = platformCompany('a');

    test()->post(route('admin.forgot_password.store'), ['email' => 'manager-a@example.com'])
        ->assertSessionHas('success');

    Notification::assertSentTimes(UserResetPassword::class, 1);

    expect(DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'user_password_resets')->where('email', 'manager-a@example.com')->exists())->toBeTrue()
        ->and(DB::connection(Tenancy::CONNECTION)->table('user_password_resets')->where('email', 'manager-a@example.com')->exists())->toBeFalse();
});

it('lets the platform reset a manager password and delete a company', function () {
    platformSetup();

    $company = platformCompany('a');

    test()->put(route('platform.companies.password', $company->id), [
        'password' => 'brand-new-pass',
        'password_confirmation' => 'brand-new-pass',
    ])->assertSessionHas('success');

    $hash = DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'users')->where('id', $company->manager_user_id)->value('password');

    expect(Hash::check('brand-new-pass', $hash))->toBeTrue();

    test()->delete(route('platform.companies.destroy', $company->id), ['confirm_name' => 'wrong'])
        ->assertSessionHasErrors('confirm_name');

    test()->delete(route('platform.companies.destroy', $company->id), ['confirm_name' => 'Company A'])
        ->assertRedirect(route('platform.dashboard'));

    expect(Company::query()->find($company->id))->toBeNull()
        ->and(Schema::connection(Tenancy::CONNECTION)->hasTable($company->table_prefix.'leads'))->toBeFalse()
        ->and(Login::query()->where('company_id', $company->id)->exists())->toBeFalse();
});

it('indexes users created by a company manager', function () {
    platformSetup();

    $company = platformCompany('a');

    test()->post(route('admin.session.store'), ['email' => 'manager-a', 'password' => 'manager-pass-123']);

    $roleId = DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'roles')->value('id');

    test()->postJson(route('admin.settings.users.store'), [
        'name' => 'Member One',
        'username' => 'member-one',
        'email' => 'member-one@example.com',
        'password' => 'member-pass-1',
        'confirm_password' => 'member-pass-1',
        'role_id' => $roleId,
        'status' => 1,
        'view_permission' => 'global',
    ])->assertSuccessful();

    expect(Login::query()->where('username', 'member-one')->value('company_id'))->toBe($company->id)
        ->and(DB::connection(Tenancy::CONNECTION)->table($company->table_prefix.'users')->where('username', 'member-one')->exists())->toBeTrue()
        ->and(DB::connection(Tenancy::CONNECTION)->table('users')->where('username', 'member-one')->exists())->toBeFalse();

    /**
     * Another company cannot take the same username.
     */
    test()->postJson(route('admin.settings.users.store'), [
        'name' => 'Duplicate',
        'username' => 'owner-x',
        'email' => 'admin@example.com',
        'role_id' => $roleId,
        'status' => 1,
        'view_permission' => 'global',
    ])->assertStatus(422);
});
